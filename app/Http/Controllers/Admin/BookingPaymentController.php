<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingPaymentRequest;
use App\Http\Requests\StoreBookingPaymentRequest;
use App\Http\Requests\UpdateBookingPaymentRequest;
use App\Mail\MessageGuestMail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingVoucher;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\LookupItem;
use App\Models\StatusPayment;
use App\Models\Team;
use App\Models\TypePayment;
use App\Models\TypePayOption;
use App\Models\User;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use File;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Services\DocumentService;

class BookingPaymentController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('booking_payment_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingPayments = BookingPayment::with(['booking', 'type_pay_option', 'type_payment', 'gateway', 'status_payment', 'updated_by', 'team'])->get();

        return view('admin.bookingPayments.index', compact('bookingPayments'));
    }

    public function create(Booking $booking)
    {
        abort_if(Gate::denies('booking_payments_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //Log::info($request->all());
        // $bookingId = $request->query('booking_id');Booking $booking
        // $booking   = Booking::findOrFail($id);

        $type_pay_options = TypePayOption::select(
            'id',
            'name',
            DB::raw("LOWER(REPLACE(name, ' ', '_')) as slug")
        )->get();
        $type_payments = TypePayment::select(
            'id',
            'name',
            DB::raw("LOWER(REPLACE(name, ' ', '_')) as slug")
        )->get();
        $gateways = Gateway::select('name', 'id')->get();
        $status_payments = StatusPayment::select('name', 'id')->get();
        $updated_bies = User::select('name', 'id')->get();

        logger()->info($booking->load('booking_items'));
        $lookups = LookupItem::where('is_default', true)->get();
        return Inertia::render('Admin/Bookings/Payment', [
            'booking' => $booking,
            'gateways' => $gateways,
            'status_payments' => $status_payments,
            'type_pay_options' => $type_pay_options,
            'type_payments' => $type_payments,
            'updated_bies' => $updated_bies,
            'mode' => 'create',
            'lookups' => $lookups,

        ]);
    }

    public function store(StoreBookingPaymentRequest $request)
    {
        $service = app(DocumentService::class);
        // dd($request->all());
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'redeemer_name' => 'required|string',
            'type_pay_option_id' => 'required|exists:type_pay_options,id',
            'type_payment_id' => 'required|exists:type_payments,id',
            //'gateway_id' => 'required|exists:gateways,id',
            'payment_date' => 'required|date',
            'ex_vat_amount' => 'required|numeric|min:0',
            'lookup_item_id' => 'required|numeric',
            'vat' => 'required|numeric|min:0',
            'vat_amount' => 'required|numeric|min:0',
            'voucher_code' => 'nullable|string',
            'redeemer_name' => 'required|string',
            'note' => 'nullable|string',
            'updated_by_id' => 'required|exists:users,id',
            'reference' => 'required|string',
            'meta' => 'nullable|string',
            'team_id' => 'nullable|exists:teams,id',
        ]);


        try {
            $booking = DB::transaction(function () use ($service, $validated) {
                //$payment = $request->all();
                $validated['status_payment_id'] = 2; // paid
                $validated['gateway_id'] = 2; // manual
                logger()->info($validated);
                $bookingPayment = BookingPayment::create($validated);
                $payment = BookingPayment::updateOrCreate(
                    ['booking_id' => $validated['booking_id'],],
                    [
                        'type_pay_option_id'   => $validated['type_pay_option_id'],     // or 'Pay Offline'
                        'type_payment_id' => $validated['type_payment_id'], // Cash // 5 eft
                        'status_payment_id' => $validated['status_payment_id'],        // 'paid', 'failed', 'cancelled'
                        'ex_vat_amount'         => $validated['ex_vat_amount'],          // full amount due or partial
                        'lookup_item_id' => $validated['lookup_item_id'],
                        'vat' => $validated['vat'],
                        'vat_amount' => ($validated['ex_vat_amount'] * $validated['vat']),
                        'reference'      => $validated['reference'],      // 'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                        'gateway_id'        => $validated['gateway_id'],        // 'Manual'
                        'meta'           => json_encode(['ip' => request()->ip()]),
                        'payment_date'   =>  now(),
                        'redeemer_name'  => $validated['redeemer_name'] ?? null, // '',
                        'voucher_code' => $validated['voucher_code'] ?? null,
                        'note'           => $validated['note'] ?? null,
                        'updated_by_id'  => $validated['updated_by_id'],
                        'team_id'        => $validated['team_id'],
                    ]
                );


                $booking = $bookingPayment->booking;
                logger()->info($booking);
                $booking->update([
                    'status_payment_id' => 2, // paid
                    'tot_paid' => $bookingPayment->ex_vat_amount,
                ]);

                AuditLog::create([
                    'description' => 'Payment recorded (admin)',
                    'subject_id' => $booking->id,
                    'subject_type' => Booking::class,
                    'user_id' => auth()->id(),
                    'properties' => json_encode([
                        'payment_id' => $bookingPayment->id,
                        'amount' => $bookingPayment->ex_vat_amount,
                        'method' => 'admin',
                    ]),
                    'host' => request()->ip(),
                ]);
                //TODO update booking item with payment amount if this is a multiple booking activity
                $balance = $bookingPayment->ex_vat_amount;
                //dd($booking->booking_items);
                $booking->booking_items->each(function ($item) use ($validated, $balance) {

                    $item->booking_fees->each(function ($fee) use ($validated, $balance) {
                        $balance = $balance - $fee->tot_amount;
                        $fee->update([
                            'tot_paid' => $balance >= 0 ? $fee->tot_amount : 0,
                            'note' => $validated['note'],
                        ]);
                    });
                });
                $customer = Customer::findOrFail($booking->customer_id);
                $invoice = Invoice::where('booking_id', $booking->id)->first();
                $customer = Customer::where('id', $booking->customer_id)->first();
                $site = Team::findorFail($invoice->team_id);
                $items = $booking?->booking_items->first();
                if (false) {
                    $subject = 'Payment Confirmation';
                    $recipient = $customer->email_address;

                    $invoiceData = [
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
                        'amount_ex_vat' => round(($booking->grand_tot_due / 1.15), 2),
                        'vat'           => round(($booking->grand_tot_due - ($booking->grand_tot_due / 1.15)), 2),
                        'vat_amount'    => $booking->grand_tot_due,
                        'amount_paid'   => ($booking->tot_paid ?? 0) * 1.15,
                        'balance_due'   => $booking->grand_tot_due - (($booking->tot_paid ?? 0) * 1.15),
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

                    $template = InvoiceTemplate::where('is_default', true)->where('type_invoice_id', 1)->first();
                    $renderer = app(\App\Services\TemplateRenderer::class);
                    $html = $renderer->render($template->content, $invoiceData);
                    $invoiceFilename = "invoices/{$invoiceData['invoice']['number']}.pdf";
                    $invoicePdf = $service->generateAndStorePdf(
                        $html,
                        $invoiceData,
                        $invoiceFilename,
                        [
                            'team_id' => $invoice->team_id,
                            'table_name' => 'invoices',
                            'table_row_id' => $invoice->id,
                            'file_name' => "{$invoiceData['invoice']['number']}.pdf",
                            'type_file_id' => 4,
                            'booking_id' => $booking->id,
                        ],
                        false // means raw HTML
                    );
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
                    // --- Voucher ---
                    $voucherData = ['items' => $items];
                    $voucherFilename = "vouchers/voucher-{$invoiceData['invoice']['number']}.pdf";
                    $voucherPdf = $service->generateAndStorePdf(
                        'emails.booking_voucher',
                        $voucherData,
                        $voucherFilename,
                        [
                            'team_id' => $voucher->team_id,
                            'table_name' => 'BookingVoucher',
                            'table_row_id' => $voucher->id,
                            'file_name' => "voucher-{$voucher->id}.pdf",
                            'type_file_id' => 6,
                            'booking_id' => $booking->id,
                        ]
                    );
                }
                // TODO : check if we should send email
                if (false) {
                    // --- Optional Email ---
                    $attachments = $service->prepareAttachments(
                        ['pdf' => $invoicePdf, 'name' => "invoice-{$invoiceData['invoice']['number']}.pdf"],
                        ['pdf' => $voucherPdf, 'name' => "voucher-{$voucher->id}.pdf"]
                    );
                    // TODO : update to use template invoice
                    // $content = "
                    //     <p>Hello {$customer->full_name},</p>
                    //     <p>Your booking has been paid.</p>
                    //     <p>Thank you for choosing Canopy Tours.</p>
                    //     <p>Your booking reference is {$booking->reference}.</p>
                    //     <p>Please Contact us if you have any questions.</p>";
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Invoice ' . $invoice->number,
                        'body'           => strip_tags($html), // or a summary
                        'attachment_path' => $invoiceFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => null,
                        'auditable_type' => 'App\Models\Invoice',
                        'auditable_id'   => $invoice->id,
                        'team_id'        => $invoice->team_id,
                    ]);

                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Booking Detail for invoice' . $invoice->number,
                        'body'           => strip_tags($html), // or a summary
                        'attachment_path' => $voucherFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => null,
                        'auditable_type' => 'App\Models\BookingVoucher',
                        'auditable_id'   => $voucher->id,
                        'team_id'        => $voucher->team_id,
                    ]);

                    // Uncomment if you want to send mail
                    $service->sendEmail($customer->email_address, 'Booking Confirmation', $invoiceData, $attachments);
                }
                return $booking;
            });
            Log::info('booking updated', [
                'booking' => $booking,
            ]);
            return redirect()->route('admin.bookings.index');
        } catch (\Throwable $e) {
            // Log the full error for debugging
            Log::error('Booking failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);
            return back()->with([
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ]);
        }
    }

    public function simulate(Request $request, Booking $booking)
    {
        abort_if(Gate::denies('booking_payments_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vatRate = LookupItem::where('is_default', true)->where('slug', '=', 'vat_rate')->first();
        if (!$vatRate) {
            return response()->json(['message' => 'VAT rate lookup missing.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payOnlineOptionId = TypePayOption::whereRaw("LOWER(REPLACE(name, ' ', '_')) = ?", ['pay_online'])
            ->orWhere('name', 'like', '%online%')
            ->value('id');
        if (!$payOnlineOptionId) {
            $payOnlineOptionId = TypePayOption::value('id');
        }

        $cardPaymentId = TypePayment::whereRaw("LOWER(REPLACE(name, ' ', '_')) = ?", ['credit_card'])
            ->orWhere('name', 'like', '%card%')
            ->value('id');
        if (!$cardPaymentId) {
            $cardPaymentId = TypePayment::value('id');
        }

        $gatewayId = Gateway::whereRaw("LOWER(REPLACE(name, ' ', '_')) = ?", ['payfast'])
            ->orWhere('name', 'like', '%payfast%')
            ->value('id');

        $amountDue = max(0, (float) $booking->grand_tot_due - (float) $booking->tot_paid);

        DB::transaction(function () use ($booking, $vatRate, $payOnlineOptionId, $cardPaymentId, $gatewayId, $amountDue) {
            $booking->loadMissing('booking_items.booking_fees');

            $payment = BookingPayment::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'payment_date' => now(),
                    'ex_vat_amount' => $amountDue,
                    'lookup_item_id' => $vatRate->id,
                    'vat' => $vatRate->rate,
                    'vat_amount' => $amountDue * $vatRate->multiplier,
                    'voucher_code' => null,
                    'redeemer_name' => $booking->lead_name ?? null,
                    'note' => 'Simulated PayFast payment',
                    'reference' => $booking->reference,
                    'meta' => json_encode(['ip' => request()->ip(), 'source' => 'admin_simulated']),
                    'type_pay_option_id' => $payOnlineOptionId,
                    'type_payment_id' => $cardPaymentId,
                    'gateway_id' => $gatewayId,
                    'status_payment_id' => 2,
                    'updated_by_id' => auth()->id(),
                    'team_id' => $booking->team_id,
                ]
            );

            $booking->update([
                'status_payment_id' => 2,
                'tot_paid' => $booking->grand_tot_due,
            ]);

            AuditLog::create([
                'description' => 'Payment recorded (admin simulated)',
                'subject_id' => $booking->id,
                'subject_type' => Booking::class,
                'user_id' => auth()->id(),
                'properties' => json_encode([
                    'payment_id' => $payment?->id,
                    'amount' => $amountDue,
                    'method' => 'admin_simulated',
                ]),
                'host' => request()->ip(),
            ]);

            $booking->booking_items->each(function ($item) {
                $item->booking_fees->each(function ($fee) {
                    $fee->update([
                        'tot_paid' => $fee->tot_amount,
                    ]);
                });
            });
        });

        return response()->json(['success' => true]);
    }

    public function sendPayLink(Request $request, Booking $booking)
    {
        abort_if(Gate::denies('booking_payments_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customer = Customer::find($booking->customer_id);
        if (!$customer || empty($customer->email_address)) {
            return response()->json(['message' => 'Booking customer email not found.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payLink = URL::temporarySignedRoute(
            'front.payment.booking.signed',
            now()->addMinutes(80),
            ['booking' => $booking->id]
        );

        $subject = "Complete your payment for booking {$booking->reference}";
        $content = "
            <p>Hello {$customer->full_name},</p>
            <p>Please use the secure link below to complete your booking payment.</p>
            <p><a href=\"{$payLink}\">Pay Now</a></p>
            <p>If you have any questions, please contact us.</p>
        ";

        Email::create([
            'sender'         => config('mail.from.address'),
            'receiver'       => $customer->email_address,
            'subject'        => $subject,
            'body'           => strip_tags($content),
            'attachment_path' => null,
            'sent'           => true,
            'sent_at'        => now(),
            'user_id'        => auth()->user()?->id,
            'auditable_type' => 'App\Models\Booking',
            'auditable_id'   => $booking->id,
            'team_id'        => $booking->team_id,
        ]);

        AuditLog::create([
            'description' => 'Email sent (pay link)',
            'subject_id' => $booking->id,
            'subject_type' => Booking::class,
            'user_id' => auth()->id(),
            'properties' => json_encode([
                'receiver' => $customer->email_address,
                'subject' => $subject,
            ]),
            'host' => request()->ip(),
        ]);

        Mail::to($customer->email_address)->send(new MessageGuestMail($subject, $content));

        return response()->json(['success' => true]);
    }

    public function edit(Booking $booking)
    {
        //dd($bookingPayment->id);
        abort_if(Gate::denies('booking_payments_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //$booking = Booking::findOrFail($request->booking_id);
        //$bookingPayment = BookingPayment::findOrFail(1);

        $booking->load('booking_payments', 'booking_payments.type_pay_option', 'booking_payments.type_payment', 'booking_payments.gateway', 'booking_payments.status_payment', 'booking_payments.updated_by', 'booking_payments.team');
        // dd($booking);
        $type_pay_options = TypePayOption::select(
            'id',
            'name',
            DB::raw("LOWER(REPLACE(name, ' ', '_')) as slug")
        )->get();
        $type_payments = TypePayment::select(
            'id',
            'name',
            DB::raw("LOWER(REPLACE(name, ' ', '_')) as slug")
        )->get();
        $gateways = Gateway::select('name', 'id')->get();
        $status_payments = StatusPayment::select('name', 'id')->get();
        $updated_bies = User::select('name', 'id')->get();
        //dd($bookingPayment);
        return Inertia::render('Admin/Bookings/Payment', [
            'mode' => 'edit',
            'booking' => $booking,
            'gateways' => $gateways,
            'status_payments' => $status_payments,
            'type_pay_options' => $type_pay_options,
            'type_payments' => $type_payments,
            'updated_bies' => $updated_bies,

        ]);
    }

    public function update(UpdateBookingPaymentRequest $request, BookingPayment $bookingPayment)
    {
        $bookingPayment->update($request->all());

        return redirect()->route('admin.booking-payments.index');
    }

    public function show(BookingPayment $bookingPayment)
    {
        abort_if(Gate::denies('booking_payment_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingPayment->load('booking', 'type_pay_option', 'type_payment', 'gateway', 'status_payment', 'updated_by', 'team');

        return view('admin.bookingPayments.show', compact('bookingPayment'));
    }

    public function destroy(BookingPayment $bookingPayment)
    {
        abort_if(Gate::denies('booking_payment_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingPayment->delete();

        return back();
    }
}
