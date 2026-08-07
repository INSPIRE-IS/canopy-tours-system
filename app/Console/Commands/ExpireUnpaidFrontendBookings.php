<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingDiscount;
use App\Models\BookingFee;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingPriceLine;
use App\Models\CustomerManifest;
use App\Models\File as BookingFile;
use App\Models\GatewayLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpireUnpaidFrontendBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid-front
        {--hours=3 : Hold window in hours from latest PayFast submit, or booking creation if no submit exists}
        {--team= : Optional team id}
        {--force : Actually expire bookings. Without this option the command is a dry run}';

    protected $description = 'Expire unpaid public frontend provisional bookings so abandoned carts release capacity.';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $teamId = $this->option('team') !== null ? (int) $this->option('team') : null;
        $force = (bool) $this->option('force');
        $now = now('Africa/Johannesburg');

        $bookings = Booking::query()
            ->with([
                'customer:id,full_name,email_address,contact_number',
                'booking_items:id,booking_id,service_id,season_id,team_id',
                'booking_items.service:id,name',
                'booking_items.booking_inventory:id,booking_item_id,date,inventory_time_slot_id,pax',
                'booking_items.booking_inventory.inventory_time_slot:id,start_time',
            ])
            ->whereNotNull('session_id')
            ->whereNull('user_id')
            ->where('status_payment_id', 1)
            ->whereRaw('COALESCE(tot_paid, 0) = 0')
            ->whereNull('deleted_at')
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
            ->whereDoesntHave('booking_payments', function ($query) {
                $query->where('status_payment_id', 2);
            })
            ->orderBy('created_at')
            ->get();

        $rows = [];
        $candidateCount = 0;
        $expiredCandidateCount = 0;
        $expiredCount = 0;

        foreach ($bookings as $booking) {
            $candidateCount++;
            $latestSubmitAt = GatewayLog::where('booking_id', $booking->id)
                ->where('event', 'submit')
                ->max('created_at');

            $holdFrom = $latestSubmitAt
                ? Carbon::parse($latestSubmitAt, 'Africa/Johannesburg')
                : Carbon::parse($booking->created_at, 'Africa/Johannesburg');
            $expiresAt = $holdFrom->copy()->addHours($hours);
            $expired = $expiresAt->lte($now);

            if (! $expired) {
                continue;
            }

            $row = [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'team_id' => $booking->team_id,
                'lead_name' => $booking->lead_name,
                'email' => $booking->customer?->email_address,
                'phone' => $booking->customer?->contact_number,
                'created_at' => optional($booking->created_at)->format('Y-m-d H:i:s'),
                'latest_payfast_submit_at' => $latestSubmitAt ? Carbon::parse($latestSubmitAt)->format('Y-m-d H:i:s') : '',
                'hold_from' => $holdFrom->format('Y-m-d H:i:s'),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'expired' => $expired ? 'yes' : 'no',
                'action' => $expired ? ($force ? 'expired' : 'would_expire') : 'kept',
                'grand_tot_due' => $booking->grand_tot_due,
                'activity_count' => $booking->booking_items->count(),
                'activities' => $this->formatActivities($booking),
            ];

            $rows[] = $row;
            $expiredCandidateCount++;

            if ($expired && $force) {
                $this->expireBooking($booking, $hours, $holdFrom, $expiresAt);
                $expiredCount++;
            }
        }

        $reportPath = count($rows) > 0 ? $this->writeCsv($rows) : null;

        $this->info(($force ? 'Expired' : 'Would expire') . " " . ($force ? $expiredCount : $expiredCandidateCount) . " booking(s). Active provisional bookings checked: {$candidateCount}");
        $this->info($reportPath ? "CSV report: storage/app/{$reportPath}" : 'No CSV report written because no bookings were expired or eligible to expire.');

        return self::SUCCESS;
    }

    private function expireBooking(Booking $booking, int $hours, Carbon $holdFrom, Carbon $expiresAt): void
    {
        DB::transaction(function () use ($booking, $hours, $holdFrom, $expiresAt) {
            $booking = Booking::with(['booking_items'])->findOrFail($booking->id);
            $bookingItemIds = $booking->booking_items->pluck('id')->all();
            $invoiceIds = Invoice::where('booking_id', $booking->id)->pluck('id')->all();

            if (!empty($invoiceIds)) {
                InvoiceItem::whereIn('invoice_id', $invoiceIds)->delete();
                Invoice::whereIn('id', $invoiceIds)->delete();
            }

            if (!empty($bookingItemIds)) {
                $bookingDiscounts = BookingDiscount::where('booking_id', $booking->id)
                    ->orWhereIn('booking_item_id', $bookingItemIds)
                    ->get();
                foreach ($bookingDiscounts as $discount) {
                    $discount->booking_discount_items()->delete();
                    $discount->delete();
                }

                BookingPriceLine::whereIn('booking_item_id', $bookingItemIds)->delete();
                CustomerManifest::whereIn('booking_item_id', $bookingItemIds)->delete();
                BookingFee::whereIn('booking_item_id', $bookingItemIds)->delete();

                foreach ($booking->booking_items as $item) {
                    $item->customers()->detach();
                    $item->booking_inventory()->delete();
                    $item->booking_detail()->delete();
                    $item->booking_staff()->delete();
                    $item->booking_infos()->delete();
                    $item->delete();
                }
            }

            BookingFile::where('booking_id', $booking->id)->delete();
            $booking->delete();

            AuditLog::create([
                'description' => 'Frontend unpaid booking expired',
                'subject_id' => $booking->id,
                'subject_type' => Booking::class,
                'user_id' => null,
                'properties' => [
                    'reference' => $booking->reference,
                    'team_id' => $booking->team_id,
                    'hours' => $hours,
                    'hold_from' => $holdFrom->format('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'reason' => 'Unpaid frontend provisional booking exceeded hold window.',
                ],
                'host' => 'console',
            ]);
        });
    }

    private function formatActivities(Booking $booking): string
    {
        return $booking->booking_items
            ->map(function (BookingItem $item) {
                $inventory = $item->booking_inventory;
                $time = $inventory?->inventory_time_slot?->start_time;

                return implode(' | ', array_filter([
                    $item->service?->name,
                    $inventory?->date,
                    $time ? substr($time, 0, 5) : null,
                    $inventory?->pax !== null ? 'pax ' . $inventory->pax : null,
                ]));
            })
            ->filter()
            ->implode('; ');
    }

    private function writeCsv(array $rows): string
    {
        Storage::disk('local')->makeDirectory('reports');
        $path = 'reports/unpaid-front-booking-expiry-' . now('Africa/Johannesburg')->format('Ymd-His') . '.csv';
        $handle = fopen(Storage::disk('local')->path($path), 'w');

        $headers = [
            'booking_id',
            'reference',
            'team_id',
            'lead_name',
            'email',
            'phone',
            'created_at',
            'latest_payfast_submit_at',
            'hold_from',
            'expires_at',
            'expired',
            'action',
            'grand_tot_due',
            'activity_count',
            'activities',
        ];

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn($header) => $row[$header] ?? '', $headers));
        }

        fclose($handle);

        return $path;
    }
}
