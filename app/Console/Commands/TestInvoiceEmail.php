<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;
use App\Models\InvoiceTemplate;
use App\Services\TemplateRenderer;
use Barryvdh\DomPDF\Facade\Pdf;


class TestInvoiceEmail extends Command
{
    protected $signature = 'invoice:test {email}';
    // php artisan invoice:test "test@test.com"

    protected $description = 'Send a test invoice email with PDF attachment';

    // public function handle(TemplateRenderer $renderer)
    public function handle()
    {
        $email = $this->argument('email');

        // fetch the first template from DB
        $template = InvoiceTemplate::where('is_default', true)->firstOrFail();

        // fake invoice data for testing
        $invoice = [
            'site' => [
                'name' => 'Magoebaskloof Canopy Tours',
                'reg_no' => '2008/018998/07',
                'address' => 'PO Box 3132, Tzaneen',
                'address_code' => '0850',
            ],
            'client' => [
                'name' => 'Jane Allison',
                'phone' => '+27834427429',
                'email' => $email,
            ],
            'invoice' => [
                'number' => '0002129',
                'date'   => now()->toDateString(),
            ],
            'booking' => [
                'ref' => 'B2D7-C9FD',
            ],
            'service' => [
                'name' => 'Canopy Tour Package',
                'adults' => 4,
                'children' => 2,
                'guides' => 1,
            ],
            'amount_ex_vat' => 'R2,086.96',
            'vat'           => 'R313.04',
            'amount_paid'   => 'R0.00',
            'balance_due'   => 'R2,400.00',
            'notes'         => 'Please pay within 7 days.',
            'adults' => 4,
            'children' => 2,
            'guides' => 1,
            'is_voucher' => false,
            'voucher_code' => null,
            'invoice_note' => null,
            'email_reminder' => null,
            'booking_note' => null,
            'amount_ex_vat' => 'R2,086.96',
            'vat'           => 'R313.04',
            'amount_paid'   => 'R0.00',
            'balance_due'   => 'R2,400.00',
            'notes'         => 'Please pay within 7 days.',
            'bank_account_owner'         => 'Magoebaskloof Canopy Tours',
            'bank_account_no'         => '1234567890',
            'bank_account_type'         => 'Cheque',
            'bank_name'         => 'ABSA',
            'branch_code'         => '632005',
            'bank' => [
                'owner' => 'Magoebaskloof Canopy Tours',
                'number' => '1234567890',
                'type'   => 'Cheque',
                'name'   => 'ABSA',
                'branch_code' => '632005',
            ],
            'items' => [
                ['description' => 'Adult Ticket', 'qty' => 4, 'price' => 'R600.00', 'subtotal' => 'R2400.00'],
            ],
        ];

        // // render HTML from DB template
        // $html = $renderer->render($template->content, $invoice);

        // // generate PDF
        // $pdf = Pdf::loadHTML($html);

        // // send mail
        // Mail::to($email)->send(new InvoiceMail($invoice, $pdf));

        $renderer = app(\App\Services\TemplateRenderer::class);
        $html = $renderer->render($template->content, $invoice);
        // renderer->render($template->content, $invoice);

        // generate PDF
        $pdf = Pdf::loadHTML($html);

        // send mail
        Mail::to($email)->send(new InvoiceMail($invoice, $pdf));

        $this->info("Test invoice sent to {$email}");
    }
}
