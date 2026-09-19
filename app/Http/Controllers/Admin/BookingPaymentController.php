<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingPaymentRequest;
use App\Http\Requests\StoreBookingPaymentRequest;
use App\Http\Requests\UpdateBookingPaymentRequest;
use App\Mail\MessageGuestMail;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\BookingExtraPaymentAllocation;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingVoucher;
use App\Models\Customer;
use App\Models\Email;
use App\Models\File as FileModel;
use App\Models\Gateway;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\TypeInvoice;
use App\Models\LookupItem;
use App\Models\StatusPayment;
use App\Models\Team;
use App\Models\TypePayment;
use App\Models\TypePayOption;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\RendererService;
use App\Services\EmailDispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
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

        $booking->load([
            'booking_items' => fn($query) => $query->withTrashed(),
            'booking_items.service',
            'booking_items.season',
            'booking_items.booking_fee' => fn($query) => $query->withTrashed(),
            'booking_items.booking_inventory' => fn($query) => $query->withTrashed(),
            'booking_items.booking_inventory.inventory_time_slot' => fn($query) => $query->withTrashed(),
            'booking_extras.payment_allocations',
            'customer',
        ]);
        $latestPayment = BookingPayment::where('booking_id', $booking->id)
            ->latest('id')
            ->first();
        $paymentRows = BookingPayment::with(['type_payment', 'status_payment', 'updated_by'])
            ->where('booking_id', $booking->id)
            ->latest('id')
            ->get();

        $additionalRefundableAmount = round($paymentRows->sum(fn(BookingPayment $payment) => (float) ($payment->additional_amount ?? 0)), 2);
        $bookingRefundableAmount = round((float) ($booking->tot_paid ?? 0), 2);
        $bookingExtras = $booking->booking_extras
            ->map(fn(BookingExtra $extra) => [
                'id' => $extra->id,
                'type' => $extra->type,
                'name' => $extra->name,
                'description' => $extra->description,
                'quantity' => (float) $extra->quantity,
                'unit_price' => (float) $extra->unit_price,
                'total_amount' => (float) $extra->total_amount,
                'allocated_amount' => round((float) $extra->payment_allocations->sum('amount'), 2),
            ])
            ->values();
        $hasExtraAllocations = $booking->booking_extras
            ->flatMap(fn(BookingExtra $extra) => $extra->payment_allocations)
            ->isNotEmpty();
        $allocatedExtrasTotal = round((float) $bookingExtras->sum('allocated_amount'), 2);
        $pendingExtrasTotal = $hasExtraAllocations
            ? round(max(0, $bookingExtras->sum('total_amount') - $allocatedExtrasTotal), 2)
            : round(max(0, $bookingExtras->sum('total_amount') - $additionalRefundableAmount), 2);
        $pendingExtrasNote = $bookingExtras
            ->map(function (array $extra) {
                if (!empty($extra['description'])) {
                    return $extra['description'];
                }

                return trim(($extra['name'] ?: 'Extra') . ' - ' . $extra['quantity'] . ' x R' . number_format($extra['unit_price'], 2, '.', ''));
            })
            ->filter()
            ->implode(', ');

        $paymentHistory = $paymentRows
            ->map(function (BookingPayment $payment) use ($booking) {
                $exVatAmount = (float) ($payment->ex_vat_amount ?? 0);
                $vatAmount = (float) ($payment->vat_amount ?? 0);
                $additionalAmount = (float) ($payment->additional_amount ?? 0);
                $bookingPaymentAmount = $vatAmount >= $exVatAmount && $exVatAmount > 0
                    ? $exVatAmount
                    : $exVatAmount + $vatAmount;
                $totalAmount = round($bookingPaymentAmount + $additionalAmount, 2);
                $message = collect([
                    $payment->short_payment_note,
                    $payment->additional_payment_note,
                ])->filter()->implode(' | ');

                return [
                    'id' => $payment->id,
                    'created_at' => optional($payment->created_at)->format('Y-m-d H:i'),
                    'payment_date' => $payment->getRawOriginal('payment_date'),
                    'settled_due_on' => $booking->getRawOriginal('due_date'),
                    'status' => $totalAmount < 0 ? 'refund' : 'success',
                    'message' => $message ?: ($payment->note ?? ''),
                    'reference' => $payment->reference,
                    'amount' => $totalAmount,
                    'method' => $payment->type_payment?->name ?? '',
                    'capturer' => $payment->updated_by?->name ?? '',
                ];
            });
        if ($paymentRows->isEmpty()) {
            $prepaidAllocations = $booking->booking_items
                ->filter(fn($item) => (bool) ($item->booking_fee?->is_prepaid_wholesaler_voucher ?? false)
                    && (float) ($item->booking_fee?->tot_paid ?? 0) > 0)
                ->map(function ($item) use ($booking) {
                    return [
                        'id' => 'prepaid-wholesaler-' . $item->id,
                        'created_at' => optional($item->booking_fee?->updated_at ?? $booking->updated_at)->format('Y-m-d H:i'),
                        'payment_date' => $booking->getRawOriginal('due_date'),
                        'settled_due_on' => $booking->getRawOriginal('due_date'),
                        'status' => 'allocated',
                        'message' => 'Prepaid voucher partner allocation. Customer-facing balance marked paid; no payment row was captured.',
                        'reference' => $booking->reference,
                        'amount' => round((float) ($item->booking_fee?->tot_paid ?? 0), 2),
                        'method' => 'Prepaid voucher partner',
                        'capturer' => '',
                    ];
                })
                ->values();

            if ($prepaidAllocations->isNotEmpty()) {
                $paymentHistory = $prepaidAllocations;
            } elseif ((float) ($booking->tot_paid ?? 0) > 0) {
                $paymentHistory = collect([[
                    'id' => 'paid-balance-' . $booking->id,
                    'created_at' => optional($booking->updated_at)->format('Y-m-d H:i'),
                    'payment_date' => $booking->getRawOriginal('due_date'),
                    'settled_due_on' => $booking->getRawOriginal('due_date'),
                    'status' => 'allocated',
                    'message' => 'Booking is marked paid, but no payment transaction row exists.',
                    'reference' => $booking->reference,
                    'amount' => round((float) ($booking->tot_paid ?? 0), 2),
                    'method' => 'System allocation',
                    'capturer' => '',
                ]]);
            }
        }
        logger()->info($booking);
        $lookups = LookupItem::defaultsForTeam(auth()->user()->team_id);
        return Inertia::render('Admin/Bookings/Payment', [
            'booking' => $booking,
            'latest_payment' => $latestPayment,
            'payment_history' => $paymentHistory,
            'booking_refundable_amount' => $bookingRefundableAmount,
            'additional_refundable_amount' => max(0, $additionalRefundableAmount),
            'booking_extras' => $bookingExtras,
            'pending_extras_total' => $pendingExtrasTotal,
            'pending_extras_note' => $pendingExtrasNote,
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
            'vat' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'additional_amount' => 'nullable|numeric|min:0',
            'voucher_code' => 'nullable|string',
            'short_payment_note' => 'nullable|string',
            'additional_payment_note' => 'nullable|string',
            'note' => 'nullable|string',
            'updated_by_id' => 'required|exists:users,id',
            'reference' => 'required|string',
            'meta' => 'nullable|string',
            'team_id' => 'nullable|exists:teams,id',
        ]);


        try {
            $booking = DB::transaction(function () use ($service, $validated) {
                $booking = Booking::withTrashed()->with('booking_items.booking_fees')->findOrFail($validated['booking_id']);
                if ($booking->trashed()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'booking_id' => 'This booking is cancelled. Reinstate it before recording a new payment.',
                    ]);
                }
                $vatLookup = LookupItem::findOrFail($validated['lookup_item_id']);
                $vatRate = (float) ($vatLookup->rate ?? 0);
                $vatMultiplier = (float) ($vatLookup->multiplier ?: (1 + $vatRate));
                $paymentTotal = round((float) $validated['ex_vat_amount'], 2);
                $additionalAmount = round((float) ($validated['additional_amount'] ?? 0), 2);
                $totalReceived = round($paymentTotal + $additionalAmount, 2);
                $amountDue = round(max(0, (float) $booking->grand_tot_due - (float) $booking->tot_paid), 2);

                if ($totalReceived <= 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'ex_vat_amount' => 'Payment amount must be greater than 0.',
                    ]);
                }

                if ($paymentTotal > $amountDue) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'ex_vat_amount' => "Booking payment amount cannot be more than the outstanding balance of {$amountDue}. Use Additional Amount for tips, food, drinks, or merchandise.",
                    ]);
                }

                $shortPaymentNote = trim((string) ($validated['short_payment_note'] ?? ''));
                $additionalPaymentNote = trim((string) ($validated['additional_payment_note'] ?? ''));

                if (abs($paymentTotal - $amountDue) > 0.01 && $shortPaymentNote === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'short_payment_note' => 'A short/adjusted payment note is required when the booking payment amount differs from the outstanding balance.',
                    ]);
                }

                if ($additionalAmount > 0 && $additionalPaymentNote === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'additional_payment_note' => 'An additional amount note is required when recording tips, food, drinks, merchandise, or other extras.',
                    ]);
                }

                $legacyNote = collect([
                    $shortPaymentNote !== '' ? "Booking payment note: {$shortPaymentNote}" : null,
                    $additionalPaymentNote !== '' ? "Additional amount note: {$additionalPaymentNote}" : null,
                    trim((string) ($validated['note'] ?? '')) !== '' ? trim((string) $validated['note']) : null,
                ])->filter()->implode("\n");

                $paymentExVat = round($paymentTotal / $vatMultiplier, 2);
                $paymentVat = round($paymentTotal - $paymentExVat, 2);
                $newPaidTotal = round(min((float) $booking->grand_tot_due, (float) $booking->tot_paid + $paymentTotal), 2);
                $isFullyPaid = $newPaidTotal >= round((float) $booking->grand_tot_due, 2);

                $validated['status_payment_id'] = $isFullyPaid ? 2 : 1;
                $validated['gateway_id'] = 2; // manual

                $bookingPayment = BookingPayment::create([
                    'booking_id' => $validated['booking_id'],
                    'type_pay_option_id' => $validated['type_pay_option_id'],
                    'type_payment_id' => $validated['type_payment_id'],
                    'status_payment_id' => $validated['status_payment_id'],
                    'ex_vat_amount' => $paymentExVat,
                    'lookup_item_id' => $validated['lookup_item_id'],
                    'vat' => $vatRate,
                    'vat_amount' => $paymentVat,
                    'additional_amount' => $additionalAmount,
                    'short_payment_note' => $shortPaymentNote ?: null,
                    'additional_payment_note' => $additionalPaymentNote ?: null,
                    'reference' => $validated['reference'],
                    'gateway_id' => $validated['gateway_id'],
                    'meta' => json_encode(['ip' => request()->ip()]),
                    'payment_date' => $validated['payment_date'],
                    'redeemer_name' => $validated['redeemer_name'] ?? null,
                    'voucher_code' => $validated['voucher_code'] ?? null,
                    'note' => $legacyNote ?: null,
                    'updated_by_id' => $validated['updated_by_id'],
                    'team_id' => $validated['team_id'],
                ]);

                $this->allocateAdditionalAmountToBookingExtras($booking, $bookingPayment, $additionalAmount, 'payment', $additionalPaymentNote);

                $booking->update([
                    'status_payment_id' => $validated['status_payment_id'],
                    'tot_paid' => $newPaidTotal,
                ]);

                AuditLog::create([
                    'description' => 'Payment recorded (admin)',
                    'subject_id' => $booking->id,
                    'subject_type' => Booking::class,
                    'user_id' => auth()->id(),
                    'properties' => json_encode([
                        'payment_id' => $bookingPayment->id,
                        'booking_amount' => $paymentTotal,
                        'additional_amount' => $additionalAmount,
                        'total_received' => $totalReceived,
                        'short_payment_note' => $shortPaymentNote ?: null,
                        'additional_payment_note' => $additionalPaymentNote ?: null,
                        'method' => 'admin',
                    ]),
                    'host' => request()->ip(),
                ]);

                $balance = $newPaidTotal;
                $booking->booking_items->each(function ($item) use ($legacyNote, &$balance) {
                    $item->booking_fees->each(function ($fee) use ($legacyNote, &$balance) {
                        $paidForFee = min((float) $fee->tot_amount, max(0, $balance));
                        $fee->update([
                            'tot_paid' => $paidForFee,
                            'note' => $legacyNote ?: null,
                        ]);
                        $balance = round($balance - $paidForFee, 2);
                    });
                });

                $this->regenerateInvoicePdf($booking);

                if (false) {
                    $customer = Customer::findOrFail($booking->customer_id);
                    $invoiceTypeId = TypeInvoice::where('slug', 'std')->value('id');
                    $invoice = Invoice::where('booking_id', $booking->id)
                        ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
                        ->firstOrFail();
                    $site = Team::findorFail($invoice->team_id);
                    $items = $booking?->booking_items->first();
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
            Log::info('payment recorded', [
                'booking_id' => $booking->id,
            ]);
            return redirect()
                ->route('admin.booking-payments.create', $booking)
                ->with('success', 'Payment recorded successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
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

    public function refund(Request $request, Booking $booking)
    {
        abort_if(Gate::denies('booking_payments_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'refund_type' => ['required', 'in:booking,additional'],
            'refund_amount' => ['required', 'numeric', 'min:0.01'],
            'refund_note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($booking, $validated) {
                $booking = Booking::lockForUpdate()->findOrFail($booking->id);
                $amount = round((float) $validated['refund_amount'], 2);
                $refundType = $validated['refund_type'];
                $note = trim((string) $validated['refund_note']);

                $latestPayment = BookingPayment::where('booking_id', $booking->id)->latest('id')->first();
                if (!$latestPayment) {
                    throw ValidationException::withMessages([
                        'refund_amount' => 'No payment has been recorded for this booking yet.',
                    ]);
                }

                $additionalAvailable = round((float) BookingPayment::where('booking_id', $booking->id)->sum('additional_amount'), 2);
                $bookingAvailable = round((float) ($booking->tot_paid ?? 0), 2);
                $available = $refundType === 'booking' ? $bookingAvailable : $additionalAvailable;

                if ($amount > $available) {
                    $label = $refundType === 'booking' ? 'booking payment' : 'additional payment';
                    throw ValidationException::withMessages([
                        'refund_amount' => "Refund amount cannot exceed the available {$label} amount of " . number_format(max(0, $available), 2, '.', ''),
                    ]);
                }

                $vatLookup = $latestPayment->lookup_item_id
                    ? LookupItem::find($latestPayment->lookup_item_id)
                    : LookupItem::where('slug', 'vat_rate')->where('team_id', $booking->team_id)->first();
                $vatRate = (float) ($vatLookup?->rate ?? 0);
                $vatMultiplier = (float) ($vatLookup?->multiplier ?: (1 + $vatRate));

                $bookingRefundTotal = $refundType === 'booking' ? $amount : 0.0;
                $additionalRefundTotal = $refundType === 'additional' ? $amount : 0.0;
                $refundExVat = $bookingRefundTotal > 0 && $vatMultiplier > 0
                    ? round($bookingRefundTotal / $vatMultiplier, 2)
                    : 0.0;
                $refundVat = $bookingRefundTotal > 0
                    ? round($bookingRefundTotal - $refundExVat, 2)
                    : 0.0;

                $payment = BookingPayment::create([
                    'booking_id' => $booking->id,
                    'type_pay_option_id' => $latestPayment->type_pay_option_id,
                    'type_payment_id' => $latestPayment->type_payment_id,
                    'status_payment_id' => $booking->status_payment_id,
                    'ex_vat_amount' => -$refundExVat,
                    'lookup_item_id' => $vatLookup?->id ?? $latestPayment->lookup_item_id,
                    'vat' => $vatRate,
                    'vat_amount' => -$refundVat,
                    'additional_amount' => -$additionalRefundTotal,
                    'short_payment_note' => $refundType === 'booking' ? $note : null,
                    'additional_payment_note' => $refundType === 'additional' ? $note : null,
                    'reference' => $booking->reference,
                    'gateway_id' => $latestPayment->gateway_id ?: 2,
                    'meta' => json_encode([
                        'ip' => request()->ip(),
                        'refund' => true,
                        'refund_type' => $refundType,
                    ]),
                    'payment_date' => now()->toDateString(),
                    'redeemer_name' => $booking->lead_name,
                    'voucher_code' => null,
                    'note' => "Refund ({$refundType}): {$note}",
                    'updated_by_id' => auth()->id(),
                    'team_id' => $booking->team_id,
                ]);

                if ($refundType === 'booking') {
                    $newPaidTotal = round(max(0, $bookingAvailable - $amount), 2);
                    $booking->update([
                        'tot_paid' => $newPaidTotal,
                        'status_payment_id' => $newPaidTotal >= round((float) $booking->grand_tot_due, 2) ? 2 : 1,
                    ]);

                    $balance = $newPaidTotal;
                    $booking->loadMissing('booking_items.booking_fees');
                    $booking->booking_items->each(function ($item) use (&$balance) {
                        $item->booking_fees->each(function ($fee) use (&$balance) {
                            $paidForFee = min((float) $fee->tot_amount, max(0, $balance));
                            $fee->update(['tot_paid' => $paidForFee]);
                            $balance = round($balance - $paidForFee, 2);
                        });
                    });

                    $this->regenerateInvoicePdf($booking);
                } elseif ($additionalRefundTotal > 0) {
                    $this->allocateAdditionalAmountToBookingExtras($booking, $payment, -$additionalRefundTotal, 'refund', $note);
                }

                AuditLog::create([
                    'description' => 'Payment refunded (admin)',
                    'subject_id' => $booking->id,
                    'subject_type' => Booking::class,
                    'user_id' => auth()->id(),
                    'properties' => json_encode([
                        'payment_id' => $payment->id,
                        'refund_type' => $refundType,
                        'refund_amount' => $amount,
                        'refund_note' => $note,
                    ]),
                    'host' => request()->ip(),
                ]);
            });

            return redirect()
                ->route('admin.booking-payments.create', $booking)
                ->with('success', 'Refund recorded successfully.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Payment refund failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $booking->id,
                'input' => $request->all(),
            ]);

            return back()->with([
                'error' => 'Something went wrong while refunding this payment. Please try again.',
            ]);
        }
    }

    private function regenerateInvoicePdf(Booking $booking): void
    {
        $typeId = TypeInvoice::where('slug', TypeInvoice::SLUG_STD)->value('id');
        $invoice = Invoice::where('booking_id', $booking->id)
            ->when($typeId, fn($query) => $query->where('type_invoice_id', $typeId))
            ->latest('id')
            ->first();

        if (!$invoice) {
            Log::warning('Admin payment recorded but no invoice was found to regenerate.', [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
            ]);
            return;
        }

        $booking->refresh()->loadMissing('team.file_logo');
        $customer = Customer::find($booking->customer_id);
        if (!$customer) {
            Log::warning('Admin payment recorded but invoice PDF could not be regenerated because customer was missing.', [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
            ]);
            return;
        }

        $data = app(RendererService::class)->invoice($booking, $customer, $invoice);
        $invoicePdf = Pdf::loadView('invoices.invoice', $data);
        $invoicePdf->setPaper('A4', 'portrait');

        $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
        Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());

        $invoiceFile = FileModel::withTrashed()
            ->where('booking_id', $booking->id)
            ->where('type_file_id', 4)
            ->where('table_name', 'invoices')
            ->where('table_row_id', $invoice->id)
            ->latest('id')
            ->first();

        if ($invoiceFile?->trashed()) {
            $invoiceFile->restore();
        }

        if ($invoiceFile) {
            $invoiceFile->update([
                'file_name' => $data['invoice']['number'] . '.pdf',
                'file_type' => 'application/pdf',
                'path' => $invoiceFilename,
            ]);
        } else {
            FileModel::create([
                'team_id' => $invoice->team_id,
                'table_name' => 'invoices',
                'table_row_id' => $invoice->id,
                'file_name' => $data['invoice']['number'] . '.pdf',
                'file_type' => 'application/pdf',
                'type_file_id' => 4,
                'path' => $invoiceFilename,
                'booking_id' => $booking->id,
            ]);
        }

        AuditLog::create([
            'description' => 'Invoice PDF regenerated after payment',
            'subject_id' => $booking->id,
            'subject_type' => Booking::class,
            'user_id' => auth()->id(),
            'properties' => json_encode([
                'invoice_id' => $invoice->id,
                'attachment' => $invoiceFilename,
                'amount_paid' => $booking->tot_paid,
                'balance_due' => max(0, (float) $booking->grand_tot_due - (float) $booking->tot_paid),
            ]),
            'host' => request()->ip(),
        ]);
    }

    public function simulate(Request $request, Booking $booking)
    {
        abort_if(Gate::denies('booking_payments_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $vatRate = LookupItem::findDefaultForTeam('vat_rate', auth()->user()->team_id);
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

        $emailDispatch = app(EmailDispatchService::class);
        if (!$emailDispatch->canSendManual($booking->team_id)) {
            return response()->json(['message' => $emailDispatch->manualDisabledMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payLink = URL::signedRoute(
            'front.payment.booking.signed',
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

    private function allocateAdditionalAmountToBookingExtras(Booking $booking, BookingPayment $payment, float $amount, string $type, ?string $note = null): void
    {
        if (abs($amount) < 0.01) {
            return;
        }

        $remaining = round(abs($amount), 2);
        $extras = BookingExtra::with('payment_allocations')
            ->where('booking_id', $booking->id)
            ->orderBy('id', $amount > 0 ? 'asc' : 'desc')
            ->get();

        foreach ($extras as $extra) {
            if ($remaining <= 0) {
                break;
            }

            $allocated = round((float) $extra->payment_allocations->sum('amount'), 2);
            $available = $amount > 0
                ? round(max(0, (float) $extra->total_amount - $allocated), 2)
                : round(max(0, $allocated), 2);

            if ($available <= 0) {
                continue;
            }

            $allocationAmount = round(min($remaining, $available), 2);
            BookingExtraPaymentAllocation::create([
                'booking_id' => $booking->id,
                'booking_extra_id' => $extra->id,
                'booking_payment_id' => $payment->id,
                'amount' => $amount > 0 ? $allocationAmount : -$allocationAmount,
                'allocation_type' => $type,
                'note' => $note,
                'team_id' => $booking->team_id,
            ]);

            $remaining = round($remaining - $allocationAmount, 2);
        }
    }

    public function edit(Booking $booking)
    {
        //dd($bookingPayment->id);
        abort_if(Gate::denies('booking_payments_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //$booking = Booking::findOrFail($request->booking_id);
        //$bookingPayment = BookingPayment::findOrFail(1);

        $booking->load('customer', 'booking_payments', 'booking_payments.type_pay_option', 'booking_payments.type_payment', 'booking_payments.gateway', 'booking_payments.status_payment', 'booking_payments.updated_by', 'booking_payments.team');
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
