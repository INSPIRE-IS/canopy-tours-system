<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Mail\BookingMail;
use App\Mail\MessageGuestMail;
use App\Models\Alert;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Customer;
use App\Models\Email;
use App\Models\GiftVoucher;
use App\Models\Invoice;
use App\Models\BookingVoucher;
use App\Models\InvoiceTemplate;
use App\Models\LookupItem;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\File;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

class PaymentController extends Controller
{
    public function booking(Booking $booking)
    {
        //dd($booking);
        $sessionId = session()->getId() ?? null;
        //logger()->info('sessionId inertia ' . $sessionId);
        if ($sessionId != $booking->session_id || $booking->status_payment_id != 1) {
            return redirect()->route('front.welcome');
        }
        $lookups = LookupItem::where('is_default', true)->where('slug', '=', 'vat_rate')->first();

        return Inertia::render('BookingPayment', [
            'booking' => $booking->load('booking_items', 'booking_items.customers', 'booking_items.booking_fee'),
            'lookups' => $lookups,
        ]);
    }

    public function gift(GiftVoucher $gift)
    {
        //dd($gift);
        $sessionId = session()->getId() ?? null;
        $lookups = LookupItem::where('is_default', true)->where('slug', '=', 'vat_rate')->first();
        //logger()->info('sessionId inertia ' . $sessionId);
        if ($sessionId != $gift->session_id || $gift->status_payment_id != 1) {
            return redirect()->route('front.welcome');
        }

        return Inertia::render('GiftPayment', [
            'gift' => $gift,
        ]);
    }

    public function simulated(Request $request)
    {

        $type = $request->type;
        $validated = $request->validate([
            // 'full_name' => 'required|string',
            // 'email' => 'required|email',
            'type' => 'required|string',
            // 'slot_date' => 'required|date',
            // 'slot_time' => 'required',
            // 'promo_code' => 'nullable|string',
            // 'adults' => 'required|integer|min:1',
            // 'children' => 'nullable|integer|min:0',
            'booking' => 'nullable|integer',
            'gift_id' => 'nullable|integer',
        ]);
        $vatRate = LookupItem::where('is_default', true)->where('slug', '=', 'vat_rate')->first();

        if ($validated['type'] === 'booking') {

            try {
                $booking = DB::transaction(function () use ($validated, $vatRate) {

                    $booking = Booking::findOrFail($validated['booking']);
                    // Mark as paid (in real app, you'd validate PayFast response)
                    $booking->update([
                        'status_payment_id' => 2, // e.g., "paid"
                        'tot_paid' => $booking->grand_tot_due,
                    ]);

                    $payment = BookingPayment::updateOrCreate(
                        ['booking_id' => $booking->id,],
                        [
                            'type_pay_option_id'   => 2,     // or 'Pay Offline'
                            'type_payment_id' => 3, // credit card
                            'status_payment_id' => 2,  // 'paid', 'failed', 'cancelled'
                            'ex_vat_amount'         => $booking->grand_tot_due, // full amount due or partial
                            'lookup_item_id' => $vatRate->id,
                            'vat' => $vatRate->rate,
                            'vat_amount' => ($booking->grand_tot_due * $vatRate->multiplier),
                            'reference'      => $booking->reference, // Str::uuid(),      // can be order ref or external tx
                            'gateway_id'        => 1,        // PayFast
                            'meta'           => json_encode(['ip' => request()->ip()]),
                            'payment_date'   =>  now(),
                            'redeemer_name'  => '', //$validated['name'] . ' ' . $validated['surname'],
                            'voucher_code' => null,
                            'note'           => '',
                            'updated_by_id'  => null,
                            'team_id'        => $booking->team_id,
                        ]
                    );

                    $grandTot = $booking->grand_tot_due;
                    foreach ($booking->booking_items as $item) {
                        $fee = $item->booking_fee;
                        $fee->tot_paid = min($fee->tot_amount, $grandTot);
                        $fee->save();
                        $grandTot -= $fee->tot_paid;
                    }

                    $alert = Alert::create([
                        'alert_text' => 'Payment Confirmation',
                        'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                        'type_alert_id' => 3, // booking confirmation
                        'team_id' => $booking->team_id,
                    ]);
                    $users = User::where('team_id', $booking->team_id)
                        ->whereHas('roles', function ($query) {
                            $query->whereIn('name', ['Manager', 'Consultant']);
                        })
                        ->with('roles')
                        ->get();
                    foreach ($users as $user) {
                        $user->alerts()->attach($alert->id); // ✅ attach by ID
                    }

                    $customer = Customer::findOrFail($booking->customer_id);
                    $invoice = Invoice::where('booking_id', $booking->id)->first();
                    $customer = Customer::where('id', $booking->customer_id)->first();
                    $site = Team::findorFail($invoice->team_id);
                    $items = $booking?->booking_items->first();

                    $subject = 'Payment Confirmation';
                    $recipient = $customer->email_address;

                    $data = [
                        'site' => [
                            'name' => $site->full_name,
                            'reg_no' => $site->reg_no,
                            'address' => $site->address,
                            'address_code' => $site->address_code,
                        ],
                        'client' => [
                            'name' => $customer->full_name,
                            'phone' => $customer->contact_number,
                            'email' => $customer->email_address,
                        ],
                        'invoice' => [
                            'number' => $invoice->invoice_number,
                            'date'   => $invoice->invoice_date,
                        ],
                        'booking' => [
                            'ref' => $booking->reference,
                        ],
                        'service' => [
                            'name' => $items->service->name,
                            'adults' => $items->pax_adults,
                            'children' => $items->pax_children,
                            'guides' => $items->pax_guides,
                        ],
                        'amount_ex_vat' => $booking->grand_tot_due,
                        'vat'           => $booking->grand_tot_due * 0.15,
                        'amount_paid'   => $booking->tot_paid,
                        'balance_due'   => ($booking->grand_tot_due * 1.15) - $booking->tot_paid,
                        'notes'         => 'Please pay within 7 days.',
                        'logo'          => '<img src="logo.png" alt="Logo" style="max-height:50px;">',
                        'bank' => [
                            'account_owner' => $site->account_owner,
                            'account_no' => $site->account_number,
                            'account_type'   => $site->account_type,
                            'name'   => $site->bank_name,
                            'branch_code' => $site->branch_code,
                        ],
                        //     'items' => [
                        //         ['description' => 'Adult Ticket', 'qty' => 4, 'price' => 'R600.00', 'subtotal' => 'R2400.00'],
                        //     ],                    
                    ];

                    $template = InvoiceTemplate::where('is_default', true)
                        ->where('type_invoice_id', 1)
                        ->first();

                    $renderer = app(\App\Services\TemplateRenderer::class);
                    $html = $renderer->render($template->content, $data);
                    $invoicePdf = Pdf::loadHTML($html);
                    $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
                    Storage::disk('local')->put($invoiceFilename, $invoicePdf->output());
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Invoice ' . $data['invoice']['number'],
                        'body'           => strip_tags($html), // or a summary
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
                    $voucher  = BookingVoucher::with('items')
                        ->where('service_id', 1)
                        ->where('season_id', 1)
                        ->first();
                    //dd($voucher);
                    $items = $voucher->items;

                    // Build replacements for placeholders
                    $replacements = [
                        '{{reference}}' => $booking->reference ?? 'REF-000000',
                        '{{provider}}' => $bookingVoucher->provider_name ?? 'Canopy Tours',
                        '{{service}}' => $bookingVoucher->service_name ?? 'Service Test',
                        '{{start_time_and_date}}' => $bookingVoucher->start_time_and_date ?? '12:00 12 Jan 2025',
                        '{{start_location}}' => $bookingVoucher->start_location ?? 'Start Location',
                        '{{end_location}}' => $bookingVoucher->end_location ?? 'End Location',
                        '{{lead_name}}' => $booking->lead_name ?? 'Test Lead',
                        '{{lead_email}}' => $booking->lead_email ?? 'test@email.com',
                        '{{number_in_party}}' => $booking->number_in_party ?? '8',
                        '{{supplier_email}}' => $bookingVoucher->supplier_email ?? 'test@email.com',
                        '{{supplier_landline}}' => $bookingVoucher->supplier_landline ?? '123456789',
                        '{{supplier_mobile}}' => $bookingVoucher->supplier_mobile ?? '123456789',
                    ];

                    // Perform replacements in the descriptions before sending to view
                    $items->transform(function ($item) use ($replacements) {
                        $item->description = strtr($item->description, $replacements);
                        return $item;
                    });

                    $random = rand(1, 100);
                    $voucherPdf = Pdf::loadView('emails.booking_voucher', compact('items'));
                    $voucherFilename  = "vouchers/voucher-{$voucher->id}.pdf";
                    Storage::put($voucherFilename, $voucherPdf->output());

                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Booking Detail for invoice' . $data['invoice']['number'],
                        'body'           => strip_tags($html), // or a summary
                        'attachment_path' => $voucherFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => null,
                        'auditable_type' => 'App\Models\BookingVoucher',
                        'auditable_id'   => $voucher->id,
                        'team_id'        => $voucher->team_id,
                    ]);
                    File::create([
                        'team_id'      => $voucher->team_id,
                        'table_name'   => 'BookingVoucher',
                        'table_row_id' => $voucher->id,
                        'file_name'    =>  "voucher-{$voucher->id}.pdf",
                        'file_type'    => "application/pdf",
                        'type_file_id' => 6, // optional enum/type
                        'path'         => $voucherFilename,
                        'booking_id'   => $booking->id,
                    ]);
                    $attachments = [
                        [
                            'data' => $invoicePdf->output(),
                            'name' => "invoice-{$data['invoice']['number']}.pdf",
                            'mime' => 'application/pdf',
                        ],
                        [
                            'data' => $voucherPdf->output(),
                            'name' => "voucher-{$voucher->id}.pdf",
                            'mime' => 'application/pdf',
                        ],
                    ];
                    // //send mail
                    Mail::to($customer->email_address)->send(new BookingMail($data, $attachments));
                    //TODO : update to use template invoice
                    // $content = "
                    //     <p>Hello {$customer->full_name},</p>
                    //     <p>Your booking has been paid.</p>
                    //     <p>Thank you for choosing Canopy Tours.</p>
                    //     <p>Your booking reference is {$booking->reference}.</p>
                    //     <p>Please Contact us if you have any questions.</p>";

                    // Email::create([
                    //     'sender'         => config('mail.from.address'),
                    //     'receiver'       => $recipient,
                    //     'subject'        => $subject,
                    //     'body'           => strip_tags($content), // or a summary
                    //     'attachment_path' => null,
                    //     'sent'           => true,
                    //     'sent_at'        => now(),
                    //     'user_id'        => null,
                    //     'auditable_type' => 'App\Models\Booking',
                    //     'auditable_id'   => $booking->id,
                    //     'team_id'        => $booking->team_id,
                    // ]);

                    // Mail::to($recipient)->send(new MessageGuestMail($subject, $content));



                    Log::info('Payment confirmation email sent', [
                        'booking_id' => $booking->id,
                        'customer' => $customer->email_address,
                    ]);

                    return $booking;
                });

                return Inertia::render('SimulatedPayment', [
                    'booking' => $booking,
                    'vatRate' => $vatRate,
                    'success' => true,
                    'booking_id' => $booking->id,
                    'redirect_url' => route('front.payment.booking', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
                ]);
                // 4. Save to booking_inventories or other linked tables
                // return response()->json([
                //     'success' => true,
                //     'booking_id' => $booking->id,
                //     'redirect_url' => route('front.payment.booking', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
                // ]);
                //return redirect()->back()->with('booking_id', $booking->id);
            } catch (\Throwable $e) {
                // Log the full error for debugging
                Log::error('Booking failed and rolled back', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'input' => $validated,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Something went wrong while saving your booking. Please try again.',
                ], 500);
            }
        } else if ($validated['type'] === 'voucher') {
            $voucher = GiftVoucher::findOrFail($validated['gift_id']);

            $voucher->update([
                'status_payment_id' => 2, // e.g., "paid"
                'paid_date' => now(),
                'redeemed' => true,
                'redeemed_date' => now(),
            ]);

            $recipient = $voucher->email_address;
            $subject = 'Payment Confirmation';
            //TODO : update to use template invoice
            $content = "
                <p>Hello {$voucher->full_name},</p>
                <p>Your booking has been paid.</p>
                <p>Thank you for choosing Canopy Tours.</p>
                <p>Your booking reference is {$voucher->reference}.</p>
                <p>Please Contact us if you have any questions.</p>";

            Email::create([
                'sender'         => config('mail.from.address'),
                'receiver'       => $recipient,
                'subject'        => $subject,
                'body'           => strip_tags($content), // or a summary
                'attachment_path' => null,
                'sent'           => true,
                'sent_at'        => now(),
                'user_id'        => null,
                'auditable_type' => 'App\Models\GiftVoucher',
                'auditable_id'   => $voucher->id,
                'team_id'        => $voucher->team_id,
            ]);

            Mail::to($recipient)->send(new MessageGuestMail($subject, $content));

            return Inertia::render('SimulatedPayment', [
                'booking' => $voucher,
            ]);
        }
    }

    public function offline(Request $request)
    {
        $type = $request->type;

        if ($type === 'booking') {
            $validated = $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'note' => 'nullable|string',
                'proof' => 'nullable|file|max:2048|mimes:jpg,jpeg,png,pdf',
            ]);
            $booking = Booking::findOrFail($validated['booking_id']);

            $filePath = null;
            if ($request->hasFile('proof')) {
                $filePath = $request->file('proof')->store('payment_proofs', 'public');
            }

            $detail =             [
                'type_pay_option_id'   => 1,     // or 'Pay Offline'
                'type_payment_id' => 1, // Cash // 5 eft
                'status_payment_id' => 1,        // 'paid', 'failed', 'cancelled'
                'amount'         => $booking->grand_tot_due,          // full amount due or partial
                'reference'      => $booking->reference, //'reference'      => 'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                'gateway_id'     => 2,        // 'Manual'
                'meta'           => json_encode([
                    'file_path' => $filePath,
                    'note' => $validated['note'],
                    'original_name' => $request->file('proof')?->getClientOriginalName(),
                ]),
                'payment_date'   =>  now(),
                'redeemer_name'  => '', //$validated['name'] . ' ' . $validated['surname'],
                'voucher_code' => null,
                'note'           => '',
                'updated_by_id'  => 1,
                'team_id'        => null,
            ];
            //dd($detail);
            $payment = BookingPayment::updateOrCreate(['booking_id' => $booking->id,], $detail);
            return redirect()->route('front.payment.review', $booking)->with('success', 'Offline payment intent saved.');
        } else if ($type === 'voucher') {
            $voucher = GiftVoucher::findOrFail($request->gift_id);

            $voucher->update([
                'status_payment_id' => 1, // e.g., "paid"
                'paid_date' => now(),
                'redeemed' => false,
                'redeemed_date' => null,
            ]);
            return redirect()->route('front.payment.gift', $voucher)->with('success', 'Offline payment intent saved.');
        }
    }


    public function invoiceDownload(Booking $booking)
    {
        //dd($booking);
        $sessionId = session()->getId() ?? null;
        //logger()->info('sessionId inertia ' . $sessionId);
        if ($sessionId != $booking->session_id || $booking->status_payment_id != 1) {
            return redirect()->route('front.welcome');
        }

        $booking = Booking::with('team.file_logo', 'booking_items', 'booking_items.customers', 'booking_items.booking_fee')->find($booking->id);
        $teamId = $booking->team_id;

        $template = InvoiceTemplate::where('is_default', true)
            ->where('type_invoice_id', 1) // invoice
            ->where('team_id', $teamId)
            ->first();

        if (!$booking) {
            $voucher = GiftVoucher::where('session_id', $sessionId)->where('status_payment_id', 1)->first();
            $customer = collect([
                'full_name'     => $voucher?->full_name,
                'email_address' => $voucher?->email_address,
                'contact_number' => $voucher?->contact_number,
            ]);
            $count = $voucher?->gift_voucher_items?->count();
            $items = $voucher?->gift_voucher_items->first();
            $teamId = $voucher?->booking_id;
        } else {
            $customer = Customer::find($booking?->customer_id);
            $items = $booking?->booking_items->first();
            $count = $booking?->booking_items?->count();
        }

        $site = Team::select('full_name', 'reg_no', 'address', 'address_code', 'account_owner', 'account_number', 'account_type', 'bank_name', 'branch_code')->find($teamId);
        $invoice = Invoice::with('items', 'items.season')->where('booking_id', $booking->id)->first();

        $services = $invoice->items->map(function ($item) {
            $services = [
                'name' => $item->service->name . ' ' . $item->season->name,
                'file_logo' => $item->service->team->file_logo->path,
                'adults' => $item->pax_adults,
                'children' => $item->pax_children,
                'guides' => $item->pax_guides,
                'price_adult' => $item->price_adult,
                'price_child' => $item->price_child,
                'guides' => $item->pax_guides,
            ];

            return $services;
        })->unique()->toArray();
        //dd($services);
        $data = [
            'site' => [
                'name' => $site->full_name,
                'reg_no' => $site->reg_no,
                'address' => $site->address,
                'address_code' => $site->address_code,
            ],
            'client' => [
                'name' => $customer->full_name,
                'phone' => $customer->contact_number,
                'email' => $customer->email_address,
            ],
            'invoice' => [
                'number' => $invoice->invoice_number,
                'date'   => $invoice->invoice_date,
            ],
            'booking' => [
                'ref' => $booking->reference,
            ],
            'services' => $services,
            'file_logo' => $booking->team->file_logo->path,
            'amount_ex_vat' => $booking->grand_tot_due,
            'vat'           => $booking->grand_tot_due * 0.15,
            'amount_paid'   => $booking->tot_paid,
            'balance_due'   => ($booking->grand_tot_due * 1.15) - $booking->tot_paid,
            'notes'         => 'Please pay within 7 days.',
            'bank' => [
                'owner' => $site->account_owner,
                'number' => $site->account_number,
                'type'   => $site->account_type,
                'name'   => $site->bank_name,
                'branch_code' => $site->branch_code,
            ],
        ];


        $html = $this->renderTemplate($template->content, $data);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        return $pdf->stream("invoice-{$booking->id}.pdf");
        // or ->download("invoice-{$invoice->id}.pdf");
    }


    private function renderTemplate(string $template, array $data): string
    {
        $logo = "<img src='/storage/" . $data['file_logo'] . "' alt='Logo' style='max-height:50px;'/>";
        $placeholders = [
            '{{logo}}'                => $logo,
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
            '{{service_name}}'        => $data['services'][0]['name'] ?? '',
            '{{service_adults}}'      => $data['services'][0]['adults'] ?? '',
            '{{service_children}}'    => $data['services'][0]['children'] ?? '',
            '{{service_guides}}'      => $data['services'][0]['guides'] ?? '',
            '{{notes}}'               => $data['notes'] ?? '',
            '{{bank_account_owner}}'  => $data['bank']['owner'] ?? '',
            '{{bank_account_no}}'     => $data['bank']['number'] ?? '',
            '{{bank_account_type}}'   => $data['bank']['type'] ?? '',
            '{{bank_name}}'           => $data['bank']['name'] ?? '',
            '{{bank_branch_code}}'    => $data['bank']['branch_code'] ?? '',
        ];
        //dd($placeholders);
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

    public function detailDownload(Booking $booking)
    {
        $sessionId = session()->getId() ?? null;
        if ($sessionId != $booking->session_id || $booking->status_payment_id != 1) {
            return redirect()->route('front.welcome');
        }
        $booking = Booking::with('team.file_logo', 'booking_items', 'booking_items.customers', 'booking_items.booking_fee')
            ->find($booking->id);
        $teamId = $booking->team_id;

        $bookingVoucher = BookingVoucher::with('items')
            ->where('service_id', 1)
            ->where('season_id', 1)
            ->where('team_id', $teamId)
            ->first();

        $items = $bookingVoucher->items;

        $items = $bookingVoucher->items;

        $replacements = [
            '{{reference}}' => $booking->reference ?? 'REF-000000',
            '{{provider}}' => $bookingVoucher->provider_name ?? 'Canopy Tours',
            '{{service}}' => $bookingVoucher->service_name ?? 'Service Test',
            '{{start_time_and_date}}' => $bookingVoucher->start_time_and_date ?? '12:00 12 Jan 2025',
            '{{start_location}}' => $bookingVoucher->start_location ?? 'Start Location',
            '{{end_location}}' => $bookingVoucher->end_location ?? 'End Location',
            '{{lead_name}}' => $booking->lead_name ?? 'Test Lead',
            '{{lead_email}}' => $booking->lead_email ?? 'test@email.com',
            '{{number_in_party}}' => $booking->number_in_party ?? '8',
            '{{supplier_email}}' => $bookingVoucher->supplier_email ?? 'test@email.com',
            '{{supplier_landline}}' => $bookingVoucher->supplier_landline ?? '123456789',
            '{{supplier_mobile}}' => $bookingVoucher->supplier_mobile ?? '123456789',

        ];

        $items->transform(function ($item) use ($replacements) {
            $item->description = strtr($item->description, $replacements);
            return $item;
        });

        $random = rand(1, 100);
        $pdf = Pdf::loadView('emails.booking_voucher', compact('items'));

        return $pdf->stream('booking-details.pdf');
    }
}
