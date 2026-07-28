<?php

namespace App\Console\Commands;

/*
|--------------------------------------------------------------------------
| Invoice PDF regeneration command
|--------------------------------------------------------------------------
|
| Purpose:
|   Re-render existing invoice PDFs after invoice display/template/business
|   rules change. This is intentionally a maintenance command, not a pricing
|   repair command. It does not recalculate booking prices, invoice totals, or
|   payments in the database.
|
| Safe usage:
|   php artisan invoices:regenerate --booking=123 --dry-run
|   php artisan invoices:regenerate --booking=123 --force
|   php artisan invoices:regenerate --team=3 --from=2026-07-01 --to=2026-07-31 --prepaid-voucher-only --dry-run
|   php artisan invoices:regenerate --team=3 --from=2026-07-01 --to=2026-07-31 --prepaid-voucher-only --force
|
| Notes:
|   - Without --force the command runs as dry-run, even if --dry-run is omitted.
|   - Date filters use invoice_date.
|   - --prepaid-voucher-only targets invoices with at least one prepaid voucher
|     partner booking item/fee.
|   - Every run writes a CSV report to storage/app/reports for review/audit.
|
*/

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\File as FileModel;
use App\Models\Invoice;
use App\Models\TypeInvoice;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RegenerateInvoices extends Command
{
    protected $signature = 'invoices:regenerate
        {--booking= : Regenerate invoices for one booking ID}
        {--team= : Limit to one team ID}
        {--from= : Start invoice date YYYY-MM-DD}
        {--to= : End invoice date YYYY-MM-DD}
        {--type=std : Invoice type slug, for example std or quote}
        {--prepaid-voucher-only : Only include invoices linked to prepaid voucher partner booking items}
        {--dry-run : Preview only; no PDFs/files are written}
        {--force : Actually regenerate and replace stored invoice PDFs}';
    // php artisan invoices:regenerate --booking=123 --dry-run
    // php artisan invoices:regenerate --booking=123 --force
    // php artisan invoices:regenerate --team=3 --from=2026-07-01 --to=2026-07-31 --prepaid-voucher-only --dry-run
    // php artisan invoices:regenerate --team=3 --from=2026-07-01 --to=2026-07-31 --prepaid-voucher-only --force
    protected $description = 'Safely regenerate stored invoice PDFs after invoice display/template changes.';

    public function handle(RendererService $renderer): int
    {
        $bookingId = $this->option('booking');
        $teamId = $this->option('team');
        $from = $this->option('from');
        $to = $this->option('to');
        $typeSlug = (string) ($this->option('type') ?: TypeInvoice::SLUG_STD);
        $prepaidOnly = (bool) $this->option('prepaid-voucher-only');
        $dryRun = !$this->option('force') || (bool) $this->option('dry-run');

        if (!$bookingId && !$teamId) {
            $this->error('Provide --booking=ID or --team=ID. Broad all-team regeneration is blocked.');
            return CommandAlias::FAILURE;
        }

        if (($from && !$this->isDate($from)) || ($to && !$this->isDate($to))) {
            $this->error('--from and --to must be YYYY-MM-DD.');
            return CommandAlias::FAILURE;
        }

        $typeInvoiceId = TypeInvoice::where('slug', $typeSlug)->value('id');
        if (!$typeInvoiceId) {
            $this->error("Invoice type not found for slug [{$typeSlug}].");
            return CommandAlias::FAILURE;
        }

        $query = Invoice::query()
            ->with([
                'typeInvoice',
                'booking.customer',
                'items.booking_inventory.booking_item.booking_fee',
                'items.booking_inventory.booking_item.wholesaler_rate',
            ])
            ->where('type_invoice_id', $typeInvoiceId)
            ->when($bookingId, fn(Builder $query) => $query->where('booking_id', $bookingId))
            ->when($teamId, fn(Builder $query) => $query->where('team_id', $teamId))
            ->when($from, fn(Builder $query) => $query->whereDate('invoice_date', '>=', $from))
            ->when($to, fn(Builder $query) => $query->whereDate('invoice_date', '<=', $to))
            ->when($prepaidOnly, function (Builder $query) {
                $query->whereHas('items.booking_inventory.booking_item', function (Builder $query) {
                    $query->whereHas('booking_fee', function (Builder $query) {
                        $query->where('is_prepaid_wholesaler_voucher', true);
                    })->orWhereHas('wholesaler_rate', function (Builder $query) {
                        $query->where('is_prepaid_voucher', true);
                    });
                });
            })
            ->orderBy('invoice_date')
            ->orderBy('id');

        $invoices = $query->get();
        if ($invoices->isEmpty()) {
            $this->warn('No invoices matched the selected filters.');
            return CommandAlias::SUCCESS;
        }

        $this->info(($dryRun ? 'Dry-run' : 'Regenerating') . " {$invoices->count()} invoice(s).");
        $rows = [];

        foreach ($invoices as $invoice) {
            $booking = $invoice->booking;
            $customer = $booking?->customer ?: ($booking?->customer_id ? Customer::find($booking->customer_id) : null);
            $existingFile = $this->invoiceFile($invoice);
            $newPath = 'invoices/' . $invoice->invoice_number . '.pdf';

            $row = [
                'mode' => $dryRun ? 'dry-run' : 'written',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'booking_id' => $booking?->id,
                'booking_reference' => $booking?->reference,
                'team_id' => $invoice->team_id,
                'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'type' => $invoice->typeInvoice?->slug,
                'prepaid_voucher_total' => '',
                'customer_invoice_total' => '',
                'old_file_path' => $existingFile?->getRawOriginal('path'),
                'new_file_path' => $newPath,
                'status' => 'pending',
                'message' => '',
            ];

            if (!$booking || !$customer) {
                $row['status'] = 'skipped';
                $row['message'] = !$booking ? 'Missing booking' : 'Missing customer';
                $rows[] = $row;
                $this->warn("Skipped invoice {$invoice->id}: {$row['message']}.");
                continue;
            }

            try {
                $data = $renderer->invoice($booking, $customer, $invoice);
                $row['prepaid_voucher_total'] = number_format((float) ($data['prepaid_voucher_total'] ?? 0), 2, '.', '');
                $row['customer_invoice_total'] = number_format((float) ($data['vat_amount'] ?? 0), 2, '.', '');

                if (!$dryRun) {
                    $invoicePdf = Pdf::loadView('invoices.invoice', $data);
                    $invoicePdf->setPaper('A4', 'portrait');
                    Storage::disk('public')->put($newPath, $invoicePdf->output());
                    $this->upsertInvoiceFile($invoice, $newPath);
                    $this->auditRegeneration($invoice, $booking, $newPath, $row);
                }

                $row['status'] = $dryRun ? 'would_regenerate' : 'regenerated';
                $rows[] = $row;
                $this->line("{$row['status']}: {$invoice->invoice_number} / {$booking->reference}");
            } catch (\Throwable $e) {
                $row['status'] = 'failed';
                $row['message'] = $e->getMessage();
                $rows[] = $row;
                $this->error("Failed invoice {$invoice->id}: {$e->getMessage()}");
            }
        }

        $reportPath = $this->writeCsvReport($rows);
        $this->info('Report written: storage/app/' . $reportPath);

        if ($dryRun) {
            $this->warn('Dry-run only. Re-run with --force to replace invoice PDFs.');
        }

        return CommandAlias::SUCCESS;
    }

    private function isDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function invoiceFile(Invoice $invoice): ?FileModel
    {
        return FileModel::withTrashed()
            ->where('booking_id', $invoice->booking_id)
            ->where('type_file_id', 4)
            ->where('table_name', 'invoices')
            ->where('table_row_id', $invoice->id)
            ->latest('id')
            ->first();
    }

    private function upsertInvoiceFile(Invoice $invoice, string $path): void
    {
        $file = $this->invoiceFile($invoice);
        if ($file?->trashed()) {
            $file->restore();
        }

        $payload = [
            'team_id' => $invoice->team_id,
            'table_name' => 'invoices',
            'table_row_id' => $invoice->id,
            'file_name' => $invoice->invoice_number . '.pdf',
            'file_type' => 'application/pdf',
            'type_file_id' => 4,
            'path' => $path,
            'booking_id' => $invoice->booking_id,
        ];

        if ($file) {
            $file->update($payload);
            return;
        }

        FileModel::create($payload);
    }

    private function auditRegeneration(Invoice $invoice, Booking $booking, string $path, array $row): void
    {
        AuditLog::create([
            'description' => 'Invoice PDF regenerated by maintenance command',
            'subject_id' => $booking->id,
            'subject_type' => Booking::class,
            'user_id' => null,
            'properties' => json_encode([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'path' => $path,
                'prepaid_voucher_total' => $row['prepaid_voucher_total'],
                'customer_invoice_total' => $row['customer_invoice_total'],
                'command' => 'invoices:regenerate',
            ]),
            'host' => gethostname() ?: null,
        ]);
    }

    private function writeCsvReport(array $rows): string
    {
        $path = 'reports/invoice-regeneration-' . now()->format('Ymd-His') . '.csv';
        Storage::makeDirectory('reports');

        $handle = fopen('php://temp', 'r+');
        $headers = array_keys($rows[0] ?? ['status' => null]);
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn($header) => $row[$header] ?? '', $headers));
        }

        rewind($handle);
        Storage::put($path, stream_get_contents($handle));
        fclose($handle);

        return $path;
    }
}
