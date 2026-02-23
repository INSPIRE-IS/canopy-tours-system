<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingMail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\Email;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

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

        if (!$invoice) {
            return redirect()->back()->with('error', 'No invoice found for this booking.');
        }

        $data = $renderService->invoice($booking, $customer);

        $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
        $invoiceFile = File::where('booking_id', $booking->id)
            ->where('type_file_id', 4)
            ->orderByDesc('id')
            ->first();
        $invoicePath = $invoiceFile?->getRawOriginal('path');
        if (!$invoicePath || !Storage::disk('public')->exists($invoicePath)) {
            $invoicePdf = Pdf::loadView('invoices.invoice', $data);
            $invoicePdf->setPaper('A4', 'portrait');
            Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());
            $invoicePath = $invoiceFilename;
            if ($invoiceFile) {
                $invoiceFile->update([
                    'file_name' => $data['invoice']['number'] . '.pdf',
                    'path' => $invoiceFilename,
                ]);
            } else {
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
            }
        }

        $invoiceEmail = Email::where('auditable_type', 'App\Models\Invoice')
            ->where('auditable_id', $invoice->id)
            ->first();
        if (!$invoiceEmail) {
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
                'body'           => strip_tags($content),
                'attachment_path' => $invoicePath,
                'sent'           => true,
                'sent_at'        => now(),
                'user_id'        => null,
                'auditable_type' => 'App\Models\Invoice',
                'auditable_id'   => $invoice->id,
                'team_id'        => $invoice->team_id,
            ]);
            AuditLog::create([
                'description' => 'Email sent (invoice resend)',
                'subject_id' => $booking->id,
                'subject_type' => Booking::class,
                'user_id' => auth()->id(),
                'properties' => json_encode([
                    'invoice_id' => $invoice->id,
                    'receiver' => $customer->email_address,
                    'subject' => 'Invoice ' . $data['invoice']['number'],
                    'attachment' => $invoicePath,
                ]),
                'host' => request()->ip(),
            ]);
        } else {
            $invoiceEmail->touch();
        }

        $bookingItem = BookingItem::with(
            'booking',
            'booking.team',
            'booking.team.file_logo',
            'customers',
            'booking_inventory.inventory_time_slot',
            'booking_inventory.inventory_type_day.type_day',
            'booking_fee'
        )->where('booking_id', $booking->id)->first();

        $passPath = null;
        if ($bookingItem) {
            $passFile = File::where('table_name', 'BookingItem')
                ->where('table_row_id', $bookingItem->id)
                ->where('booking_id', $booking->id)
                ->orderByDesc('id')
                ->first();
            $passPath = $passFile?->getRawOriginal('path');
            if (!$passPath || !Storage::disk('public')->exists($passPath)) {
                $passData = app(BookingsController::class)->getPassDetails($bookingItem);
                $html = view('pdfs.boarding-pass', ['data' => $passData])->render();
                $dir = "pass/{$bookingItem->id}";
                $passFilename = "{$dir}/boarding_pass_{$bookingItem->id}.pdf";
                $disk = Storage::disk('public');
                $fullPath = $disk->path($passFilename);
                $disk->makeDirectory($dir);
                $chromePath = config('browsershot.chrome_path');
                if (empty($chromePath)) {
                    Log::warning('Browsershot chrome path not resolved; using default detection.', [
                        'env_path' => env('BROWSERSHOT_CHROME_PATH'),
                    ]);
                }
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
                $passPath = $passFilename;
                if ($passFile) {
                    $passFile->update([
                        'file_name' => $passFilename,
                        'path' => $passFilename,
                    ]);
                } else {
                    File::create([
                        'team_id'      => $bookingItem->team_id,
                        'table_name'   => 'BookingItem',
                        'table_row_id' => $bookingItem->id,
                        'file_name'    => $passFilename,
                        'file_type'    => "application/pdf",
                        'type_file_id' => 6,
                        'path'         => $passFilename,
                        'booking_id'   => $booking->id,
                    ]);
                }
            }
        }

        $attachments = [
            [
                'data' => Storage::disk('public')->get($invoicePath),
                'name' => "invoice-{$data['invoice']['number']}.pdf",
                'mime' => 'application/pdf',
            ],
        ];

        if ($passPath) {
            $attachments[] = [
                'data' => Storage::disk('public')->get($passPath),
                'name' => basename($passPath),
                'mime' => 'application/pdf',
            ];
        }

        Mail::to($customer->email_address)->send(new BookingMail($data, $attachments));
        return redirect()->back()->with('success', 'Invoice resent');
    }
}
