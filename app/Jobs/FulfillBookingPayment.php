<?php

namespace App\Jobs;

use App\Http\Controllers\Front\PaymentController;
use App\Mail\BookingMail;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\Email;
use App\Models\File;
use App\Models\Invoice;
use App\Models\TypeInvoice;
use App\Services\EmailDispatchService;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class FulfillBookingPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $bookingId)
    {
    }

    public function handle(EmailDispatchService $emailDispatch, RendererService $renderer): void
    {
        Cache::lock("booking-payment-fulfilment:{$this->bookingId}", 120)->block(10, function () use ($emailDispatch, $renderer) {
            $booking = Booking::with('team.file_logo')->findOrFail($this->bookingId);

            if ((int) $booking->status_payment_id !== 2) {
                throw new RuntimeException('Booking payment fulfilment requires a confirmed payment.');
            }

            if (! $emailDispatch->canSendAutomatic(
                $booking->team_id,
                EmailDispatchService::SETTING_AUTO_PAYMENT
            )) {
                return;
            }

            $customer = Customer::findOrFail($booking->customer_id);
            $invoiceTypeId = TypeInvoice::where('slug', TypeInvoice::SLUG_STD)->value('id');
            $invoice = Invoice::query()
                ->where('booking_id', $booking->id)
                ->when($invoiceTypeId, fn ($query) => $query->where('type_invoice_id', $invoiceTypeId))
                ->firstOrFail();
            $data = $renderer->invoice($booking, $customer, $invoice);
            $invoicePath = $this->invoicePath($booking, $invoice, $data);
            $passPath = $this->boardingPassPath($booking);
            $subject = $data['document']['email_subject']
                ?? trim(($data['document']['label'] ?? 'Invoice') . ' ' . $data['invoice']['number']);

            $email = Email::firstOrNew([
                'auditable_type' => Invoice::class,
                'auditable_id' => $invoice->id,
                'subject' => $subject,
            ]);
            $email->fill([
                'sender' => config('mail.from.address'),
                'receiver' => $customer->email_address,
                'body' => implode("\n", $data['document']['email_message_lines'] ?? []),
                'attachment_path' => $invoicePath,
                'user_id' => null,
                'team_id' => $booking->team_id,
            ]);
            if (! $email->exists) {
                $email->sent = false;
                $email->sent_at = null;
            }
            $email->save();

            if ($email->sent) {
                return;
            }

            $attachments = [[
                'data' => Storage::disk('public')->get($invoicePath),
                'name' => strtolower($data['document']['label'] ?? 'invoice') . "-{$data['invoice']['number']}.pdf",
                'mime' => 'application/pdf',
            ]];
            if ($passPath) {
                $attachments[] = [
                    'data' => Storage::disk('public')->get($passPath),
                    'name' => basename($passPath),
                    'mime' => 'application/pdf',
                ];
            }

            Mail::to($customer->email_address)->send(new BookingMail($data, $attachments));

            $email->update([
                'sent' => true,
                'sent_at' => now(),
            ]);
            AuditLog::firstOrCreate(
                [
                    'description' => 'Email sent (booking payment)',
                    'subject_id' => $booking->id,
                    'subject_type' => Booking::class,
                ],
                [
                    'user_id' => null,
                    'properties' => json_encode([
                        'invoice_id' => $invoice->id,
                        'receiver' => $customer->email_address,
                        'subject' => $subject,
                        'invoice_attachment' => $invoicePath,
                        'boarding_pass_attachment' => $passPath,
                    ]),
                    'host' => null,
                ]
            );
        });
    }

    private function invoicePath(Booking $booking, Invoice $invoice, array $data): string
    {
        $path = 'invoices/' . $data['invoice']['number'] . '.pdf';
        $pdf = Pdf::loadView('invoices.invoice', $data)->setPaper('A4', 'portrait');
        Storage::disk('public')->put($path, $pdf->output());

        File::withTrashed()->updateOrCreate(
            [
                'table_name' => 'invoices',
                'table_row_id' => (string) $invoice->id,
                'booking_id' => $booking->id,
                'type_file_id' => 4,
            ],
            [
                'team_id' => $booking->team_id,
                'file_name' => $data['invoice']['number'] . '.pdf',
                'file_type' => 'application/pdf',
                'path' => $path,
                'deleted_at' => null,
            ]
        );

        return $path;
    }

    private function boardingPassPath(Booking $booking): ?string
    {
        $bookingItem = BookingItem::with(
            'booking',
            'booking.team',
            'booking.team.file_logo',
            'service',
            'customers',
            'booking_inventory.inventory_time_slot',
            'booking_inventory.inventory_type_day.type_day',
            'booking_fee'
        )->where('booking_id', $booking->id)->first();

        if (! $bookingItem) {
            return null;
        }

        $path = "pass/{$bookingItem->id}/boarding_pass_{$bookingItem->id}.pdf";
        $existing = File::withTrashed()
            ->where('table_name', 'BookingItem')
            ->where('table_row_id', $bookingItem->id)
            ->where('booking_id', $booking->id)
            ->where('type_file_id', 6)
            ->latest('id')
            ->first();
        $existingPath = $existing?->getRawOriginal('path');
        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            return $existingPath;
        }

        $passData = app(PaymentController::class)->getPassDetails($bookingItem);
        $html = view('pdfs.boarding-pass', ['data' => $passData])->render();
        $disk = Storage::disk('public');
        $disk->makeDirectory("pass/{$bookingItem->id}");
        $chromePath = config('browsershot.chrome_path');
        if (empty($chromePath)) {
            Log::warning('Browsershot chrome path not resolved; using default detection.', [
                'env_path' => env('BROWSERSHOT_CHROME_PATH'),
            ]);
        }
        $shot = Browsershot::html($html);
        if (! empty($chromePath)) {
            $shot->setChromePath($chromePath);
        }
        $shot->format('A4')
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->scale(1.0)
            ->timeout(60000)
            ->save($disk->path($path));

        File::withTrashed()->updateOrCreate(
            [
                'table_name' => 'BookingItem',
                'table_row_id' => (string) $bookingItem->id,
                'booking_id' => $booking->id,
                'type_file_id' => 6,
            ],
            [
                'team_id' => $booking->team_id,
                'file_name' => basename($path),
                'file_type' => 'application/pdf',
                'path' => $path,
                'deleted_at' => null,
            ]
        );

        return $path;
    }
}
