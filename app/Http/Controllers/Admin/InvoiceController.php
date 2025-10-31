<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingMail;
use App\Models\Booking;
use App\Models\BookingVoucher;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function preview(Request $request)
    {
        $data = $request->all(); // booking form draft
        //dd($data);
        $template = InvoiceTemplate::where('is_default', true)->where('type', 'invoice')->first();

        //$template = file_get_contents('invoice-template.html');

        $data = [
            'logo' => '<img src="/assets/icons/logo-icon.png" alt="Logo" style="max-height:50px;">',
            'site' => [
                'name' => 'Magoebaskloof Canopy Tours',
                'reg_no' => '2008/018998/07',
                'address' => 'PO Box 3132, Tzaneen',
                'address_code' => '0850',
            ],
            'client' => [
                'name' => 'Jane Allison',
                'phone' => '+27834427429',
                'email' => 'jane@thabametsi.com',
            ],
            'invoice' => [
                'number' => '0002129',
                'date'   => '2025-09-13',
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
            'logo'          => '<img src="logo.png" alt="Logo" style="max-height:50px;">',
            'bank' => [
                'owner' => 'Magoebaskloof Canopy Tours',
                'number' => '1234567890',
                'type'   => 'Cheque',
                'name'   => 'ABSA',
                'branch_code' => '632005',
            ],
        ];

        $html = $this->renderTemplate($template->content, $data);
        return response($html)
            ->header('Content-Type', 'text/html');
        //return response()->json(['html' => $html]);
    }

    public function download(Request $request)
    {
        $data = $request->all(); // booking form draft
        //dd($data);
        $template = InvoiceTemplate::where('is_default', true)->first();
        $data = [
            'site' => [
                'name' => 'Magoebaskloof Canopy Tours',
                'reg_no' => '2008/018998/07',
                'address' => 'PO Box 3132, Tzaneen',
                'address_code' => '0850',
            ],
            'client' => [
                'name' => 'Jane Allison',
                'phone' => '+27834427429',
                'email' => 'jane@thabametsi.com',
            ],
            'invoice' => [
                'number' => '0002129',
                'date'   => '2025-09-13',
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
            'logo'          => '<img src="logo.png" alt="Logo" style="max-height:50px;">',
            'bank' => [
                'owner' => 'Magoebaskloof Canopy Tours',
                'number' => '1234567890',
                'type'   => 'Cheque',
                'name'   => 'ABSA',
                'branch_code' => '632005',
            ],
        ];


        $html = $this->renderTemplate($template->content, $data);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        return $pdf->stream("invoice-{$request->id}.pdf");
        // or ->download("invoice-{$invoice->id}.pdf");
    }

    public function previewSaved(Invoice $invoice)
    {
        $template = $invoice->template ?? InvoiceTemplate::where('is_default', true)->first();

        $html = $this->renderTemplate($template->content, $invoice->toArray());

        return inertia('Admin/Invoices/Preview', ['html' => $html]);
    }

    private function renderTemplate(string $template, array $data): string
    {
        $placeholders = [
            '{{logo}}'                => $data['logo'] ?? '',
            '{{site_name}}'           => $data['site']['name'] ?? '',
            '{{site_reg_no}}'         => $data['site']['reg_no'] ?? '',
            '{{site_address}}'        => $data['site']['address'] ?? '',
            '{{site_address_code}}'   => $data['site']['address_code'] ?? '',
            '{{client_name}}'         => $data['client']['name'] ?? '',
            '{{client_phone}}'        => $data['client']['phone'] ?? '',
            '{{client_email}}'        => $data['client']['email'] ?? '',
            '{{invoice_number}}'      => $data['invoice']['number'] ?? '',
            '{{invoice_date}}'        => $data['invoice']['date'] ?? '',
            '{{booking_ref}}'         => $data['booking']['ref'] ?? '',
            '{{amount_ex_vat}}'       => $data['amount_ex_vat'] ?? '',
            '{{vat}}'                 => $data['vat'] ?? '',
            '{{amount_paid}}'         => $data['amount_paid'] ?? '',
            '{{balance_due}}'         => $data['balance_due'] ?? '',
            '{{service_name}}'        => $data['service']['name'] ?? '',
            '{{adults}}'              => $data['service']['adults'] ?? '',
            '{{children}}'            => $data['service']['children'] ?? '',
            '{{guides}}'              => $data['service']['guides'] ?? '',
            '{{notes}}'               => $data['notes'] ?? '',
            '{{logo}}'                => $data['logo'] ?? '',
            '{{bank_account_owner}}'  => $data['bank']['owner'] ?? '',
            '{{bank_account_no}}'     => $data['bank']['number'] ?? '',
            '{{bank_account_type}}'   => $data['bank']['type'] ?? '',
            '{{bank_name}}'           => $data['bank']['name'] ?? '',
            '{{branch_code}}'         => $data['bank']['branch_code'] ?? '',
        ];

        $html = strtr($template, $placeholders);

        // Handle dynamic line items if template includes {{lineItems}}
        if (str_contains($html, '{{lineItems}}')) {
            $rows = '';
            foreach ($data['items'] ?? [] as $item) {
                $rows .= "<tr>
                        <td>{$item['description']}</td>
                        <td>{$item['qty']}</td>
                        <td>{$item['price']}</td>
                        <td>{$item['subtotal']}</td>
                    </tr>";
            }
            $html = str_replace('{{lineItems}}', $rows, $html);
        }

        return $html;
    }

    public function resend_invoice($booking_id)
    {
        $invoice = Invoice::where('booking_id', $booking_id)->first();
        $booking = Booking::findOrFail($booking_id);
        $customer = Customer::findOrFail($booking->customer_id);
        $renderService = app(RendererService::class);

        $data = $renderService->invoice($booking, $customer);
        $invoicePdf = Pdf::loadView('invoices.invoice', $data);
        $invoicePdf->setPaper('A4', 'portrait');
        $invoiceEmail = Email::Where('auditable_type', 'App\Models\Invoice')
            ->Where('auditable_id', $invoice->id)
            ->first();
        if (!$invoiceEmail) {
            $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
            Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());
            $content = "
                        <p>Hello {$customer->full_name},</p>
                        <p>Your booking has been paid.</p>
                        <p>Thank you for choosing Canopy Tours.</p>
                        <p>Your booking reference is {$booking->reference}.</p>
                        <p>Please Contact us if you have any questions.</p>";
            Email::create([
                'sender'         => config('mail.from.address'),
                'receiver'       => $customer->email_address,
                'subject'        => 'Invoice ' . $data['invoice']['number'],
                'body'           => strip_tags($content), // strip_tags($html)or a summary
                'attachment_path' => $invoiceFilename,
                'sent'           => true,
                'sent_at'        => now(),
                'user_id'        => null,
                'auditable_type' => 'App\Models\Invoice',
                'auditable_id'   => $invoice->id,
                'team_id'        => $invoice->team_id,
            ]);
            File::create([
                'team_id'      => $invoice->team_id,
                'table_name'   => 'invoices',
                'table_row_id' => $invoice->id,
                'file_name'    => $data['invoice']['number'] . '.pdf',
                'file_type'    => "application/pdf",
                'type_file_id' => 4, // invoice
                'path'         => $invoiceFilename,
                'booking_id'   => $booking->id,
            ]);
        } else {
            $invoiceEmail->touch();
            // Email::create([
            //     'sender'         => $invoiceEmail->sender,
            //     'receiver'       => $invoiceEmail->receiver,
            //     'subject'        => $invoiceEmail->subject,
            //     'body'           => $invoiceEmail->body,
            //     'attachment_path' => $invoiceEmail->attachment_path,
            //     'sent'           => true,
            //     'sent_at'        => now(),
            //     'user_id'        => null,
            //     'auditable_type' => 'App\Models\Email',
            //     'auditable_id'   => $invoiceEmail->id,
            //     'team_id'        => $invoiceEmail->team_id,
            // ]);
            // $invoiceEmail->delete();
        }

        //TODO : update to use the actual booking voucher
        $bookingVoucher  = BookingVoucher::with('items')
            ->where('service_id', 1)
            ->where('season_id', 1)
            ->first();

        $items = $renderService->voucher($booking, $bookingVoucher);
        $random = rand(1, 100);
        $voucherPdf = Pdf::loadView('emails.booking_voucher', compact('items'));

        $voucherEmail = Email::Where('auditable_type', 'App\Models\BookingVoucher')
            ->Where('auditable_id', $bookingVoucher->id)
            ->first();
        if (!$voucherEmail) {
            $voucherFilename = 'vouchers/voucher-' . $bookingVoucher->id . '.pdf';
            Storage::disk('public')->put($voucherFilename, $voucherPdf->output());
            $content = "
                    <p>Hello {$customer->full_name},</p>
                    <p>Your booking has been paid.</p>
                    <p>Thank you for choosing Canopy Tours.</p>
                    <p>Your booking reference is {$booking->reference}.</p>
                    <p>Please Contact us if you have any questions.</p>";
            Email::create([
                'sender'         => config('mail.from.address'),
                'receiver'       => $customer->email_address,
                'subject'        => 'Booking Detail for invoice' . $data['invoice']['number'],
                'body'           => strip_tags($content), // or a summary
                'attachment_path' => $voucherFilename,
                'sent'           => true,
                'sent_at'        => now(),
                'user_id'        => null,
                'auditable_type' => 'App\Models\BookingVoucher',
                'auditable_id'   => $bookingVoucher->id,
                'team_id'        => $bookingVoucher->team_id,
            ]);
            File::create([
                'team_id'      => $bookingVoucher->team_id,
                'table_name'   => 'BookingVoucher',
                'table_row_id' => $bookingVoucher->id,
                'file_name'    =>  "voucher-{$bookingVoucher->id}.pdf",
                'file_type'    => "application/pdf",
                'type_file_id' => 6, // optional enum/type
                'path'         => $voucherFilename,
                'booking_id'   => $booking->id,
            ]);
        } else {
            $voucherEmail->touch();
            // Email::create([
            //     'sender'         => $voucherEmail->sender,
            //     'receiver'       => $voucherEmail->receiver,
            //     'subject'        => $voucherEmail->subject,
            //     'body'           => $voucherEmail->body,
            //     'attachment_path' => $voucherEmail->attachment_path,
            //     'sent'           => true,
            //     'sent_at'        => now(),
            //     'user_id'        => null,
            //     'auditable_type' => 'App\Models\Email',
            //     'auditable_id'   => $voucherEmail->id,
            //     'team_id'        => $voucherEmail->team_id,
            // ]);
            // $voucherEmail->delete();
        }

        $attachments = [
            [
                'data' => $invoicePdf->output(),
                'name' => "invoice-{$data['invoice']['number']}.pdf",
                'mime' => 'application/pdf',
            ],
            [
                'data' => $voucherPdf->output(),
                'name' => "voucher-{$bookingVoucher->id}.pdf",
                'mime' => 'application/pdf',
            ],
        ];
        Mail::to($customer->email_address)->send(new BookingMail($data, $attachments));
        return redirect()->back()->with('success', 'Invoice resent');
    }
}
