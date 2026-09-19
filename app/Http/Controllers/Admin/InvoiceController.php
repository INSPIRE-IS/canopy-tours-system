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
use App\Models\Team;
use App\Models\TypeInvoice;
use App\Services\RendererService;
use App\Services\EmailDispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;

class InvoiceController extends Controller
{
    public function previewTeam(Team $team)
    {
        abort_if(Gate::denies('teams_show'), 403);

        $team->loadMissing('file_logo', 'file_invoice_quote_icon');

        $logoPath = $this->normalizePublicStoragePath($team->file_logo?->getRawOriginal('path'));
        $invoiceQuoteIconPath = $this->normalizePublicStoragePath($team->file_invoice_quote_icon?->getRawOriginal('path')) ?: $logoPath;
        $logoSrc = $this->publicImageSource($logoPath ?: 'images/logo.png');
        $invoiceQuoteIconSrc = $this->publicImageSource($invoiceQuoteIconPath ?: ($logoPath ?: 'images/logo.png')) ?: $logoSrc;
        $addressLines = array_values(array_filter([
            $team->address_line_1 ?: ($team->address ?: 'PO Box / Street Address Placeholder'),
            $team->address_line_2,
            $team->city ?: 'City Placeholder',
            $team->postal_code ?: ($team->address_code ?: 'Postal Code Placeholder'),
        ]));
        $bankName = trim(implode(' - ', array_filter([
            $team->bank_name ?: 'Bank Name Placeholder',
            $team->bank_branch_name,
        ])));

        $total = 2400.00;
        $amountExVat = round($total / 1.15, 2);

        $data = [
            'document' => [
                'label' => 'Quote',
                'number_label' => 'Quote #',
            ],
            'site' => [
                'name' => $team->full_name ?: ($team->name ?: 'Site Name Placeholder'),
                'reg_no' => $team->reg_no ?: 'Reg No Placeholder',
                'vat_no' => $team->vat_no ?: 'VAT No Placeholder',
                'address_lines' => $addressLines,
                'telephone' => $team->telephone ?: 'Telephone Placeholder',
                'email' => $team->email ?: 'email@example.com',
                'website_url' => $team->website_url ?: 'www.example.com',
                'invoice_note' => $team->invoice_note ?: 'Cancellation policy / invoice note placeholder.',
            ],
            'client' => [
                'name' => 'Attention / Group Name Placeholder',
                'phone' => 'Client Telephone Placeholder',
                'email' => 'client@example.com',
            ],
            'invoice' => [
                'number' => 'PREVIEW',
                'date' => now()->toDateString(),
            ],
            'booking' => [
                'ref' => 'REF-PREVIEW',
            ],
            'services' => [
                [
                    'name' => 'Sample Activity / Service',
                    'date' => now()->toDateString(),
                    'time' => '09:00',
                    'adults' => 2,
                    'children' => 1,
                    'guides' => 0,
                    'total' => $total,
                ],
            ],
            'file_logo' => $logoSrc ?: ($logoPath ?: 'images/logo.png'),
            'invoice_quote_icon' => $invoiceQuoteIconSrc ?: ($logoSrc ?: ($invoiceQuoteIconPath ?: ($logoPath ?: 'images/logo.png'))),
            'amount_ex_vat' => $amountExVat,
            'vat' => round($total - $amountExVat, 2),
            'vat_amount' => $total,
            'amount_paid' => 0,
            'balance_due' => $total,
            'notes' => 'Booking invoice note placeholder.',
            'bank' => [
                'owner' => $team->account_owner ?: 'Account Name Placeholder',
                'number' => $team->account_number ?: 'Account Number Placeholder',
                'type' => $team->account_type ?: 'Account Type Placeholder',
                'name' => $bankName,
                'branch_name' => $team->bank_branch_name,
                'branch_code' => $team->branch_code ?: 'Branch Code Placeholder',
            ],
        ];

        return Pdf::loadView('invoices.invoice', $data)
            ->setPaper('A4', 'portrait')
            ->stream("site-invoice-preview-{$team->id}.pdf");
    }

    public function preview(Request $request)
    {
        $data = $request->all(); // booking form draft
        //dd($data);
        $typeId = TypeInvoice::where('slug', 'std')->value('id');
        $template = InvoiceTemplate::where('is_default', true)
            ->when($typeId, fn($query) => $query->where('type_invoice_id', $typeId))
            ->first();

        //$template = file_get_contents('invoice-template.html');

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
        return response($html)
            ->header('Content-Type', 'text/html');
        //return response()->json(['html' => $html]);
    }

    public function download(Request $request)
    {
        $data = $request->all(); // booking form draft
        //dd($data);
        $typeId = TypeInvoice::where('slug', 'std')->value('id');
        $template = InvoiceTemplate::where('is_default', true)
            ->when($typeId, fn($query) => $query->where('type_invoice_id', $typeId))
            ->first();
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
        $template = $invoice->template ?? InvoiceTemplate::where('is_default', true)
            ->when($invoice->type_invoice_id, fn($query) => $query->where('type_invoice_id', $invoice->type_invoice_id))
            ->first();

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

    private function publicImageSource(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['data:', 'http://', 'https://'])) {
            return $path;
        }

        $relativePath = ltrim($path, '/');
        $absolutePath = Str::startsWith($relativePath, 'storage/')
            ? storage_path('app/public/' . substr($relativePath, strlen('storage/')))
            : public_path($relativePath);

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/png';
        return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($absolutePath));
    }


    private function normalizePublicStoragePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            $parsedPath = parse_url($path, PHP_URL_PATH);
            if (is_string($parsedPath) && str_contains($parsedPath, '/storage/')) {
                $storagePos = strrpos($parsedPath, '/storage/');
                return ltrim(substr($parsedPath, $storagePos + 1), '/');
            }

            return null;
        }

        $path = ltrim($path, '/');
        if (str_contains($path, '/storage/')) {
            $storagePos = strrpos($path, '/storage/');
            return ltrim(substr($path, $storagePos + 1), '/');
        }

        if (Str::startsWith($path, ['storage/', 'images/'])) {
            return $path;
        }

        return 'storage/' . $path;
    }

    public function resend_invoice($booking_id)
    {
        $typeId = TypeInvoice::where('slug', 'std')->value('id');
        $invoice = Invoice::where('booking_id', $booking_id)
            ->when($typeId, fn($query) => $query->where('type_invoice_id', $typeId))
            ->first();
        $booking = Booking::findOrFail($booking_id);
        $customer = Customer::findOrFail($booking->customer_id);
        $renderService = app(RendererService::class);
        $emailDispatch = app(EmailDispatchService::class);

        if (!$emailDispatch->canSendManual($booking->team_id)) {
            return redirect()->back()->with('error', $emailDispatch->manualDisabledMessage());
        }

        if (!$invoice) {
            return redirect()->back()->with('error', 'No invoice found for this booking.');
        }

        $data = $renderService->invoice($booking, $customer, $invoice);

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
                'subject'        => ($data['document']['label'] ?? 'Invoice') . ' ' . $data['invoice']['number'],
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
                    'subject' => ($data['document']['label'] ?? 'Invoice') . ' ' . $data['invoice']['number'],
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
                'name' => strtolower($data['document']['label'] ?? 'invoice') . "-{$data['invoice']['number']}.pdf",
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
