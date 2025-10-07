<?php

namespace App\Console\Commands;

use App\Models\Email;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Str;

class TestBladeInvoiceEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-blade-invoice-email {email}';

    // php artisan app:test-blade-invoice-email <email>
    // php artisan app:test-blade-invoice-email jason@example.com

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Mock invoice data (adjust as needed)
        $invoice = [
            'site' => [
                'name' => 'Magoebaskloof Canopy Tours',
                'reg_no' => '2008/018998/07',
                'vat_no' => '4010252619',
                'address' => 'PO Box 3132, Tzaneen',
                'address_code' => '0850',
            ],
            'client' => [
                'name' => 'Jane Allison',
                'phone' => '+27834427429',
                'email' => $email,
                'address' => "Reisadvies Zuid-Afrika\nLage Akkers 4\n5554 GJ Valkenswaard\nThe Netherlands",
            ],
            'invoice' => [
                'number' => '0002129',
                'date'   => now()->toDateString(),
            ],
            'booking' => [
                'ref' => 'B2D7-C9FD',
            ],
            'service' => [
                'name' => 'Canopy Tour (11:30 / 9 Sep 2025)',
                'adults' => 4,
                'children' => 2,
                'guides' => 1,
            ],
            'invoice' => [
                'number'      => '0003713',
                'proforma'    => '0001703',
                'booking'     => '72B7-C09D',
                'date'        => '2025-05-05',
                'amount_ex_vat'  => '1670.00',
                'discount'    => '334.00',
                'subtotal'    => '1161.74',
                'vat'         => '174.26',
                'amount_due'       => '1336.00',
                'amount_paid'        => '0.00',
                'balance_due' => '1336.00',
            ],
            'notes'         => 'Please pay within 7 days.',
            'is_voucher' => false,
            'voucher_code' => null,
            'invoice_note' => null,
            'email_reminder' => null,
            'booking_note' => null,
            'bank' => [
                'account_owner' => 'Magoebaskloof Canopy Tours',
                'account_no' => '1234567890',
                'account_type'   => 'Cheque',
                'name'   => 'ABSA',
                'branch_code' => '632005',
            ],
            'items' => [
                ['description' => 'Adult Ticket', 'qty' => 4, 'price' => 'R600.00', 'subtotal' => 'R2400.00'],
            ],
        ];

        // 1. Generate PDF from Blade
        $pdf = Pdf::loadView('emails.invoice_item', compact('invoice'));
        $pdf->setPaper('a4');
        // $pdf->setMargins(20, 20, 20, 20);
        // $pdf->setFont('Helvetica', './fonts/Helvetica.ttf', 10);
        // $pdf->setFont('Helvetica-Bold', './fonts/Helvetica-Bold.ttf', 10);
        // $pdf->setFont('Helvetica-Oblique', './fonts/Helvetica-Oblique.ttf', 10);
        $bookingRef = 'REF-' . strtoupper(Str::random(6));
        // 2. Save PDF copy to storage
        //$fileName = 'invoice_' . $invoice['invoice']['number'] . '.pdf';
        $fileName = 'invoice_' . $bookingRef . '.pdf';
        $filePath = 'invoices/' . $fileName;

        Storage::put($filePath, $pdf->output());

        // 3. Send email with PDF attachment
        // Mail::send('emails.invoice-test', ['invoice' => $invoice], function ($message) use ($email, $pdf, $fileName) {
        //     $message->to($email)
        //         ->subject('Test Invoice')
        //         ->attachData($pdf->output(), $fileName);
        // });

        // 4. Save email record in DB
        Email::create([
            'sender'         => config('mail.from.address'),
            'receiver'       => $email,
            'subject'        => 'Test Invoice',
            'body'           => 'Please find your test invoice attached.',
            'attachment_path' => $filePath,
            'sent'           => true,
            'sent_at'        => now(),
            'user_id'        => auth()->id() ?? null,
            'auditable_type' => null,
            'auditable_id'   => null,
            'team_id'        => null,
        ]);

        $this->info("✅ Test invoice sent and stored at: storage/app/{$filePath}");
    }
}
