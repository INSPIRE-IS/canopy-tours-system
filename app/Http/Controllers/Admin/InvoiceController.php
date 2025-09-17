<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
}
