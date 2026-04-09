<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\BookingsController;
use App\Models\Booking;
use App\Models\BookingDiscount;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\File;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\TypeInvoice;
use App\Services\PricingService;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Throwable;

class RepairBookingPricing extends Command
{
    protected $signature = 'bookings:repair-pricing
        {--team= : Team ID}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d)}
        {--include-paid : Include fully paid bookings}
        {--dry-run : Do not persist changes}
        {--csv= : CSV output path}
        {--fix-leads : Create/attach a lead customer when booking lead_name does not match}
        {--update-invoices : Reissue invoice PDFs}
        {--update-boarding-pass : Reissue boarding pass PDFs (requires Chrome/Browsershot)}
    ';
    // Run examples

    // Dry‑run + CSV (no DB writes):
    // php artisan bookings:repair-pricing --team=5 --from=2026-01-01 --to=2026-06-30 --dry-run --fix-leads
    // php artisan bookings:repair-pricing --from=2026-01-01 --to=2026-06-30 --dry-run --fix-leads
    // This writes a CSV under storage/app/reports/ by default.

    // Apply fixes + reissue invoices:
    // php artisan bookings:repair-pricing --team=5 --from=2026-01-01 --to=2026-06-30 --fix-leads --update-invoices
    // Include fully paid bookings (not default):
    // php artisan bookings:repair-pricing --include-paid
    // Notes

    // Invoice reissue is skipped (with a warning log) for bookings that have multiple booking items.
    // Boarding pass reissue is optional: add --update-boarding-pass if you want it.

    protected $description = 'Recalculate booking totals using PricingService and repair historical records.';

    public function handle(): int
    {
        $teamId = $this->option('team') ? (int) $this->option('team') : null;
        $from = $this->option('from');
        $to = $this->option('to');
        $includePaid = (bool) $this->option('include-paid');
        $dryRun = (bool) $this->option('dry-run');
        $fixLeads = (bool) $this->option('fix-leads');
        $updateInvoices = (bool) $this->option('update-invoices');
        $updateBoardingPass = (bool) $this->option('update-boarding-pass');

        $pricingService = app(PricingService::class);

        $csvPath = $this->resolveCsvPath($dryRun);
        $csvHandle = null;
        if ($csvPath) {
            $csvHandle = $this->openCsv($csvPath);
            fputcsv($csvHandle, [
                'booking_id',
                'booking_item_id',
                'reference',
                'booking_date',
                'service_id',
                'season_id',
                'adults',
                'children',
                'guides',
                'current_total',
                'new_total',
                'diff',
                'status',
                'lead_fix',
                'invoice_reissued',
                'boarding_pass_reissued',
                'note',
            ]);
        }

        $query = BookingItem::with([
            'booking',
            'booking.booking_discounts.booking_discount_items.discount',
            'booking_fee',
            'booking_inventory.inventory_override',
            'booking_inventory.inventory_time_slot',
            'customers',
            'season',
        ]);

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($from || $to) {
            $query->whereHas('booking_inventory', function ($subQuery) use ($from, $to) {
                if ($from) {
                    $subQuery->whereDate('date', '>=', $from);
                }
                if ($to) {
                    $subQuery->whereDate('date', '<=', $to);
                }
            });
        }

        $items = $query->get();
        $this->info("Found {$items->count()} booking items to evaluate.");

        $updatedBookings = [];
        $invoiceQueue = [];
        $boardingQueue = [];

        foreach ($items as $item) {
            $booking = $item->booking;
            $fee = $item->booking_fee;
            $inventory = $item->booking_inventory;

            $row = [
                'booking_id' => $booking?->id,
                'booking_item_id' => $item->id,
                'reference' => $booking?->reference,
                'booking_date' => $inventory?->getRawOriginal('date') ?? $inventory?->date,
                'service_id' => $item->service_id,
                'season_id' => $item->season_id,
                'adults' => $inventory?->adults,
                'children' => $inventory?->children,
                'guides' => $inventory?->guides,
                'current_total' => $fee?->tot_amount,
                'new_total' => null,
                'diff' => null,
                'status' => 'skipped',
                'lead_fix' => '',
                'invoice_reissued' => '',
                'boarding_pass_reissued' => '',
                'note' => '',
            ];

            try {
                if (!$booking || !$fee || !$inventory) {
                    $row['note'] = 'missing booking/fee/inventory';
                    $this->writeCsvRow($csvHandle, $row);
                    continue;
                }

                $currentTotal = (float) ($fee->tot_amount ?? 0);
                $paidTotal = (float) ($fee->tot_paid ?? 0);

                if (!$includePaid && $currentTotal > 0 && $paidTotal >= $currentTotal) {
                    $row['note'] = 'fully paid';
                    $this->writeCsvRow($csvHandle, $row);
                    continue;
                }

                $bookingDate = $this->normalizeDate($inventory?->getRawOriginal('date') ?? $inventory?->date);
                if (!$bookingDate) {
                    $row['note'] = 'missing booking date';
                    $this->writeCsvRow($csvHandle, $row);
                    continue;
                }

                $override = $inventory->inventory_override;
                $overrideRates = $override && (
                    $override->price_adult !== null
                    || $override->price_child !== null
                    || $override->price_guide !== null
                );

                $discountLines = $this->buildDiscountLines($booking);

                $pricing = $pricingService->calculate([
                    'service_id' => $item->service_id,
                    'season_id' => $item->season_id,
                    'booking_date' => $bookingDate,
                    'wholesaler_id' => $item->wholesaler_id,
                    'promotion_id' => $item->promotion_id,
                    'adults' => (int) ($inventory->adults ?? 0),
                    'children' => (int) ($inventory->children ?? 0),
                    'guides' => (int) ($inventory->guides ?? 0),
                    'discount_lines' => $discountLines,
                    'override_rates' => $overrideRates,
                    'override_rate_adult' => $override?->price_adult,
                    'override_rate_child' => $override?->price_child,
                    'override_rate_guide' => $override?->price_guide,
                ]);

                $newTotal = (float) ($pricing['total_price'] ?? 0);
                $diff = round($newTotal - $currentTotal, 2);

                $row['new_total'] = $newTotal;
                $row['diff'] = $diff;
                $row['status'] = abs($diff) > 0.01 ? 'needs_update' : 'ok';

                $leadFixNote = '';
                if ($fixLeads) {
                    $leadFixNote = $this->fixLeadCustomer($booking, $item, $dryRun);
                    $row['lead_fix'] = $leadFixNote;
                }

                $computedDiscountLines = $pricing['discount_lines'] ?? [];

                if (!$dryRun && abs($diff) > 0.01) {
                    DB::transaction(function () use ($item, $inventory, $booking, $pricing, $computedDiscountLines) {
                        $rates = $pricing['applied_rates'] ?? [];
                        $totals = $pricing['totals_by_type'] ?? [];
                        $item->update([
                            'price_adult' => round((float) ($rates['adult'] ?? 0), 2),
                            'price_child' => round((float) ($rates['child'] ?? 0), 2),
                            'price_guide' => round((float) ($rates['guide'] ?? 0), 2),
                            'pax_adults' => (int) ($inventory->adults ?? 0),
                            'pax_children' => (int) ($inventory->children ?? 0),
                            'pax_guides' => (int) ($inventory->guides ?? 0),
                        ]);

                        $inventory->update([
                            'pax' => (int) ($inventory->adults ?? 0) + (int) ($inventory->children ?? 0),
                        ]);

                        $item->booking_fee?->update([
                            'tot_adults' => round((float) ($totals['adult'] ?? 0), 2),
                            'tot_children' => round((float) ($totals['child'] ?? 0), 2),
                            'tot_guides' => round((float) ($totals['guide'] ?? 0), 2),
                            'tot_amount' => round((float) ($pricing['total_price'] ?? 0), 2),
                        ]);

                        $this->syncBookingDiscounts($booking, $pricing, $computedDiscountLines);
                    });

                    $updatedBookings[$booking->id] = $booking->id;
                    if ($updateInvoices) {
                        $invoiceQueue[$booking->id] = $booking->id;
                        $row['invoice_reissued'] = 'queued';
                    }
                    if ($updateBoardingPass) {
                        $boardingQueue[$booking->id] = $booking->id;
                        $row['boarding_pass_reissued'] = 'queued';
                    }
                    $row['status'] = 'updated';
                }

                $this->writeCsvRow($csvHandle, $row);
            } catch (Throwable $e) {
                Log::error('booking repair failed', [
                    'booking_item_id' => $item->id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $row['status'] = 'error';
                $row['note'] = $e->getMessage();
                $this->writeCsvRow($csvHandle, $row);
            }
        }

        if (!$dryRun && $updatedBookings) {
            foreach (array_keys($updatedBookings) as $bookingId) {
                $booking = Booking::with('booking_items.booking_fee')->find($bookingId);
                if (!$booking) {
                    continue;
                }
                $total = $booking->booking_items
                    ->pluck('booking_fee')
                    ->filter()
                    ->sum(fn($fee) => (float) ($fee->tot_amount ?? 0));
                $booking->update(['grand_tot_due' => round($total, 2)]);
            }
        }

        if (!$dryRun && $updateInvoices && $invoiceQueue) {
            foreach (array_keys($invoiceQueue) as $bookingId) {
                $booking = Booking::with([
                    'booking_items.booking_inventory',
                    'booking_items.booking_fee',
                    'booking_items.customers',
                    'customer',
                ])->find($bookingId);
                if (!$booking) {
                    continue;
                }
                if ($booking->booking_items->count() > 1) {
                    Log::warning('Skipping invoice reissue for multi-item booking', [
                        'booking_id' => $booking->id,
                        'items' => $booking->booking_items->count(),
                    ]);
                    continue;
                }
                $bookingItem = $booking->booking_items->first();
                if (!$bookingItem) {
                    continue;
                }
                $this->reissueInvoice($booking, $bookingItem);
            }
        }

        if (!$dryRun && $updateBoardingPass && $boardingQueue) {
            foreach (array_keys($boardingQueue) as $bookingId) {
                $booking = Booking::with([
                    'booking_items.booking_inventory.inventory_time_slot',
                    'booking_items.customers',
                    'booking_items.booking_fee',
                    'booking_items.booking.team.file_logo',
                ])->find($bookingId);
                if (!$booking) {
                    continue;
                }
                if ($booking->booking_items->count() > 1) {
                    Log::warning('Skipping boarding pass reissue for multi-item booking', [
                        'booking_id' => $booking->id,
                        'items' => $booking->booking_items->count(),
                    ]);
                    continue;
                }
                $bookingItem = $booking->booking_items->first();
                if (!$bookingItem) {
                    continue;
                }
                $this->reissueBoardingPass($bookingItem);
            }
        }

        if ($csvHandle) {
            fclose($csvHandle);
            $this->info("CSV written to {$csvPath}");
        }

        $this->info('Booking repair completed.');

        return Command::SUCCESS;
    }

    protected function normalizeDate($value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function resolveCsvPath(bool $dryRun): ?string
    {
        $csvPath = $this->option('csv');
        if ($csvPath) {
            return $csvPath;
        }
        if (!$dryRun) {
            return null;
        }
        $dir = storage_path('app/reports');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir . DIRECTORY_SEPARATOR . 'booking-repair-' . now()->format('Ymd-His') . '.csv';
    }

    protected function openCsv(string $path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return fopen($path, 'w');
    }

    protected function writeCsvRow($handle, array $row): void
    {
        if (!$handle) {
            return;
        }
        fputcsv($handle, array_values($row));
    }

    protected function buildDiscountLines(Booking $booking): array
    {
        $discountItems = $booking->booking_discounts
            ->flatMap(fn($discount) => $discount->booking_discount_items ?? collect())
            ->filter()
            ->values();

        $lines = [];
        foreach ($discountItems as $item) {
            $type = $item->pax_type ?? null;
            if (!in_array($type, ['adult', 'child', 'guide'], true)) {
                continue;
            }
            $count = (int) ($item->count ?? 0);
            if ($count <= 0) {
                continue;
            }
            $lines[] = [
                'type' => $type,
                'count' => $count,
                'discount_id' => $item->discount_id,
            ];
        }

        return $lines;
    }

    protected function syncBookingDiscounts(Booking $booking, array $pricing, array $discountLines): void
    {
        $bookingDiscount = BookingDiscount::where('booking_id', $booking->id)->first();
        $rateAdult = round((float) ($pricing['applied_rates']['adult'] ?? 0), 2);
        $rateChild = round((float) ($pricing['applied_rates']['child'] ?? 0), 2);
        $rateGuide = round((float) ($pricing['applied_rates']['guide'] ?? 0), 2);

        if (empty($discountLines)) {
            if ($bookingDiscount) {
                $bookingDiscount->booking_discount_items()->delete();
                $bookingDiscount->delete();
            }
            return;
        }

        if (!$bookingDiscount) {
            $bookingDiscount = BookingDiscount::create([
                'booking_id' => $booking->id,
                'final_price_adult' => $rateAdult,
                'final_price_child' => $rateChild,
                'final_price_guide' => $rateGuide,
            ]);
        } else {
            $bookingDiscount->update([
                'final_price_adult' => $rateAdult,
                'final_price_child' => $rateChild,
                'final_price_guide' => $rateGuide,
            ]);
        }

        $bookingDiscount->booking_discount_items()->delete();

        $grouped = collect($discountLines)->groupBy(function ($line) {
            return ($line['type'] ?? '') . '|' . ($line['discount_id'] ?? '');
        });

        foreach ($grouped as $lines) {
            $line = $lines->first();
            $count = $lines->sum('count');
            $bookingDiscount->booking_discount_items()->create([
                'pax_type' => $line['type'],
                'discount_id' => $line['discount_id'],
                'count' => $count,
                'base_rate' => $line['base_rate'] ?? 0,
                'final_rate' => $line['final_rate'] ?? 0,
            ]);
        }
    }

    protected function fixLeadCustomer(Booking $booking, BookingItem $bookingItem, bool $dryRun): string
    {
        $leadName = trim((string) $booking->lead_name);
        if ($leadName === '') {
            return 'missing_lead_name';
        }

        $leadCustomer = $bookingItem->customers()->wherePivot('is_lead', 1)->first();
        $bookingCustomer = $booking->customer;

        $leadMatches = $leadCustomer
            && $this->normalizeName($leadCustomer->full_name) === $this->normalizeName($leadName);

        if ($leadMatches) {
            if (!$dryRun && $booking->customer_id !== $leadCustomer->id) {
                $booking->update(['customer_id' => $leadCustomer->id]);
                return 'synced_booking_customer';
            }
            return 'ok';
        }

        if (
            !$leadCustomer && $bookingCustomer
            && $this->normalizeName($bookingCustomer->full_name) === $this->normalizeName($leadName)
        ) {
            if (!$dryRun) {
                $bookingItem->customers()->syncWithoutDetaching([
                    $bookingCustomer->id => [
                        'team_id' => $bookingItem->team_id ?? $booking->team_id,
                        'is_lead' => 1,
                    ],
                ]);
                $booking->update(['customer_id' => $bookingCustomer->id]);
            }
            return 'attached_existing_customer';
        }

        if ($dryRun) {
            return 'would_create_customer';
        }

        $source = $leadCustomer ?? $bookingCustomer;
        $newCustomer = Customer::create([
            'full_name' => $leadName,
            'email_address' => $source?->email_address,
            'contact_number' => $source?->contact_number,
            'nationality_id' => $source?->nationality_id,
        ]);

        if ($leadCustomer) {
            $bookingItem->customers()->updateExistingPivot($leadCustomer->id, [
                'is_lead' => 0,
            ]);
        }

        $bookingItem->customers()->syncWithoutDetaching([
            $newCustomer->id => [
                'team_id' => $bookingItem->team_id ?? $booking->team_id,
                'is_lead' => 1,
            ],
        ]);

        $booking->update([
            'customer_id' => $newCustomer->id,
            'lead_name' => $leadName,
        ]);

        return 'created_new_customer';
    }

    protected function normalizeName(?string $name): string
    {
        $value = strtolower(trim((string) $name));
        $value = preg_replace('/\s+/', ' ', $value);
        return $value ?? '';
    }

    protected function reissueInvoice(Booking $booking, BookingItem $bookingItem): void
    {
        $bookingDate = $this->normalizeDate($bookingItem->booking_inventory?->getRawOriginal('date') ?? $bookingItem->booking_inventory?->date)
            ?? now()->toDateString();

        $amount = (float) ($bookingItem->booking_fee?->tot_amount ?? 0);
        $rates = [
            'adult' => (float) ($bookingItem->price_adult ?? 0),
            'child' => (float) ($bookingItem->price_child ?? 0),
            'guide' => (float) ($bookingItem->price_guide ?? 0),
        ];

        $invoiceTypeId = TypeInvoice::where('slug', TypeInvoice::SLUG_STD)->value('id');
        $existingInvoiceIds = Invoice::withTrashed()
            ->where('booking_id', $booking->id)
            ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
            ->pluck('id');

        if ($existingInvoiceIds->isNotEmpty()) {
            InvoiceItem::whereIn('invoice_id', $existingInvoiceIds)->delete();
            Invoice::whereIn('id', $existingInvoiceIds)->delete();

            File::where('booking_id', $booking->id)
                ->where('table_name', 'invoices')
                ->where('type_file_id', 4)
                ->whereIn('table_row_id', $existingInvoiceIds)
                ->delete();
        }

        $template = InvoiceTemplate::where('is_default', true)
            ->where('type_invoice_id', $invoiceTypeId)
            ->where('team_id', $booking->team_id)
            ->first();

        $amountExVat = round(($amount / 1.15), 2);
        $vatAmount = round(($amount - $amountExVat), 2);
        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'gift_voucher_id' => null,
            'invoice_date' => $bookingDate,
            'due_date' => $bookingDate,
            'subtotal' => $amountExVat,
            'vat' => $vatAmount,
            'total' => $amount,
            'status' => 'draft',
            'invoice_template_id' => $template?->id,
            'type_invoice_id' => $invoiceTypeId,
            'team_id' => $booking->team_id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'pax_adults' => (int) ($bookingItem->booking_inventory?->adults ?? 0),
            'pax_children' => (int) ($bookingItem->booking_inventory?->children ?? 0),
            'pax_guides' => (int) ($bookingItem->booking_inventory?->guides ?? 0),
            'price_adult' => $rates['adult'],
            'price_child' => $rates['child'],
            'price_guide' => $rates['guide'],
            'team_id' => $booking->team_id,
            'service_id' => $bookingItem->service_id,
            'season_id' => $bookingItem->season_id,
            'booking_inventory_id' => $bookingItem->booking_inventory?->id,
            'promotion_id' => $bookingItem->promotion_id,
            'wholesaler_id' => $bookingItem->wholesaler_id,
            'discount_id' => null,
        ]);

        $customer = $booking->customer
            ?? $bookingItem->customers()->wherePivot('is_lead', 1)->first()
            ?? $bookingItem->customers()->first();

        $renderService = app(RendererService::class);
        $data = $renderService->invoice($booking, $customer, $invoice);
        $invoicePdf = Pdf::loadView('invoices.invoice', $data);
        $invoicePdf->setPaper('A4', 'portrait');

        $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
        Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());

        File::create([
            'team_id' => $booking->team_id,
            'table_name' => 'invoices',
            'table_row_id' => $invoice->id,
            'file_name' => $data['invoice']['number'] . '.pdf',
            'file_type' => 'application/pdf',
            'type_file_id' => 4,
            'path' => $invoiceFilename,
            'booking_id' => $booking->id,
        ]);
    }

    protected function reissueBoardingPass(BookingItem $bookingItem): void
    {
        $controller = app(BookingsController::class);
        $passData = $controller->getPassDetails($bookingItem);
        $html = view('pdfs.boarding-pass', ['data' => $passData])->render();

        $dir = "pass/{$bookingItem->id}";
        $fileName = "{$dir}/boarding_pass_{$bookingItem->id}.pdf";
        $disk = Storage::disk('public');
        $fullPath = $disk->path($fileName);

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $chromePath = config('browsershot.chrome_path');
        $shot = Browsershot::html($html);
        if (!empty($chromePath)) {
            $shot->setChromePath($chromePath);
        }
        $shot->format('A4')
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->scale(1.0)
            ->timeout(60000)
            ->save($fullPath);

        File::where('booking_id', $bookingItem->booking_id)
            ->where('table_name', 'BookingItem')
            ->where('type_file_id', 6)
            ->delete();

        File::create([
            'team_id' => $bookingItem->team_id,
            'table_name' => 'BookingItem',
            'table_row_id' => $bookingItem->id,
            'file_name' => $fileName,
            'file_type' => 'application/pdf',
            'type_file_id' => 6,
            'path' => $fileName,
            'booking_id' => $bookingItem->booking_id,
        ]);
    }
}
