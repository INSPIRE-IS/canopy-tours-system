<?php

namespace App\Jobs;

use App\Mail\MessageGuestMail;
use App\Models\AuditLog;
use App\Models\Email;
use App\Models\File;
use App\Models\GiftVoucher;
use App\Services\EmailDispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FulfillGiftVoucherPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $voucherId)
    {
    }

    public function handle(EmailDispatchService $emailDispatch): void
    {
        Cache::lock("gift-voucher-fulfillment:{$this->voucherId}", 120)->block(10, function () use ($emailDispatch) {
            $voucher = GiftVoucher::with([
                'gift_voucher_items.team.file_logo',
                'gift_voucher_items.service',
                'gift_voucher_items.season',
            ])->findOrFail($this->voucherId);

            if ((int) $voucher->status_payment_id !== 2) {
                throw new RuntimeException('Gift voucher fulfilment requires a confirmed payment.');
            }

            $voucherItem = $voucher->gift_voucher_items->first();
            $team = $voucherItem?->team;
            if (! $voucherItem || ! $team) {
                throw new RuntimeException('Gift voucher fulfilment requires voucher item and site details.');
            }

            $voucherCode = $voucher->voucher_code ?: ('VOU-' . $voucher->id);
            $voucherFilename = 'gift_vouchers/' . $voucherCode . '.pdf';
            $logoPath = $this->localLogoPath($team->file_logo?->path);
            $voucherPdf = Pdf::loadView('vouchers.gift', [
                'voucher' => $voucher,
                'voucher_code' => $voucherCode,
                'team' => $team,
                'service' => $voucherItem->service,
                'season' => $voucherItem->season,
                'logo_path' => $logoPath,
                'pax_adults' => $voucherItem->pax_adults,
                'pax_children' => $voucherItem->pax_children,
                'amount' => $voucher->grand_tot_due,
            ])->setPaper('A4', 'portrait');

            Storage::disk('public')->put($voucherFilename, $voucherPdf->output());

            $file = File::withTrashed()->updateOrCreate(
                [
                    'table_name' => 'gift_vouchers',
                    'table_row_id' => (string) $voucher->id,
                    'path' => $voucherFilename,
                ],
                [
                    'team_id' => $team->id,
                    'file_name' => $voucherCode . '.pdf',
                    'file_type' => 'application/pdf',
                    'type_file_id' => null,
                    'booking_id' => null,
                    'deleted_at' => null,
                ]
            );
            if ($file->trashed()) {
                $file->restore();
            }

            $siteName = $team->name ?: 'our team';
            $subject = "Your {$siteName} Gift Voucher";
            $contact = collect([$team->email, $team->telephone])->filter()->implode(' or ');
            $contactLine = $contact
                ? "<p>If you have any questions, please contact {$siteName} at {$contact}.</p>"
                : "<p>If you have any questions, please contact {$siteName}.</p>";
            $content = "
                <p>Hello {$voucher->full_name},</p>
                <p>Thank you for purchasing a gift voucher from {$siteName}. Your voucher is attached and your voucher code is <strong>{$voucherCode}</strong>.</p>
                <p>We have received the payment notification. Payment will be verified by {$siteName} when the voucher is used. Please keep your PayFast confirmation or other proof of payment in case our team asks for it.</p>
                {$contactLine}
                <p>We look forward to welcoming you.</p>";

            $email = Email::firstOrNew([
                'auditable_type' => GiftVoucher::class,
                'auditable_id' => $voucher->id,
                'subject' => $subject,
            ]);
            $email->fill([
                'sender' => config('mail.from.address'),
                'receiver' => $voucher->email_address,
                'body' => strip_tags($content),
                'attachment_path' => $voucherFilename,
                'user_id' => null,
                'team_id' => $team->id,
            ]);
            if (! $email->exists) {
                $email->sent = false;
                $email->sent_at = null;
            }
            $email->save();

            if ($email->sent || ! $emailDispatch->canSendAutomatic(
                $team->id,
                EmailDispatchService::SETTING_AUTO_PAYMENT
            )) {
                return;
            }

            Mail::to($voucher->email_address)->send(new MessageGuestMail($subject, $content, [[
                'data' => $voucherPdf->output(),
                'name' => $voucherCode . '.pdf',
                'mime' => 'application/pdf',
            ]]));

            $email->update([
                'sent' => true,
                'sent_at' => now(),
            ]);

            AuditLog::firstOrCreate(
                [
                    'description' => 'Email sent (gift voucher payment)',
                    'subject_id' => $voucher->id,
                    'subject_type' => GiftVoucher::class,
                ],
                [
                    'user_id' => null,
                    'properties' => json_encode([
                        'receiver' => $voucher->email_address,
                        'subject' => $subject,
                        'attachment' => $voucherFilename,
                    ]),
                    'host' => request()?->ip(),
                ]
            );
        });
    }

    private function localLogoPath(?string $logoPath): ?string
    {
        if (! $logoPath) {
            return null;
        }

        if (str_starts_with($logoPath, 'http')) {
            $logoPath = parse_url($logoPath, PHP_URL_PATH) ?: $logoPath;
        }
        if (str_starts_with($logoPath, '/storage/')) {
            $logoPath = substr($logoPath, strlen('/storage/'));
        }

        $path = public_path('storage/' . ltrim($logoPath, '/'));

        return is_file($path) ? $path : null;
    }
}
