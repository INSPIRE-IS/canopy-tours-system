<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Mail\BookingMail;
use App\Mail\MessageGuestMail;
use App\Models\Alert;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BoardingPassTemplate;
use App\Models\Customer;
use App\Models\Email;
use App\Models\GiftVoucher;
use App\Models\Invoice;
use App\Models\BookingVoucher;
use App\Models\InvoiceTemplate;
use App\Models\TypeInvoice;
use App\Models\LookupItem;
use App\Models\Team;
use App\Models\TypeAlert;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\File;
use App\Models\User;
use App\Services\RendererService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
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
        $teamId = $booking->team_id;
        $lookups = LookupItem::findDefaultForTeam('vat_rate', $teamId);

        return Inertia::render('BookingPayment', [
            'booking' => $booking->load('booking_items', 'booking_items.customers', 'booking_items.booking_fee'),
            'lookups' => $lookups,
        ]);
    }

    public function bookingSigned(Booking $booking)
    {
        if ($booking->status_payment_id != 1) {
            return redirect()->route('front.welcome');
        }

        $teamId = $booking->team_id;
        $lookups = LookupItem::findDefaultForTeam('vat_rate', $teamId);

        return Inertia::render('BookingPayment', [
            'booking' => $booking->load('booking_items', 'booking_items.customers', 'booking_items.booking_fee'),
            'lookups' => $lookups,
        ]);
    }

    public function gift(GiftVoucher $gift)
    {
        //dd($gift);
        $sessionId = session()->getId() ?? null;
        $teamId = $gift->team_id;
        $lookups = LookupItem::findDefaultForTeam('vat_rate', $teamId);
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
        if ($validated['type'] === 'booking') {

            try {
                $booking = DB::transaction(function () use ($validated) {
                    $renderService = app(RendererService::class);
                    $booking = Booking::findOrFail($validated['booking']);
                    $vatRate = LookupItem::findDefaultForTeam('vat_rate', $booking->team_id);
                    // Mark as paid (in real app, you'd validate PayFast response)
                    $booking->update([
                        'status_payment_id' => 2, // e.g., "paid"
                        'tot_paid' => $booking->grand_tot_due,
                    ]);

                    $totalDue = (float) $booking->grand_tot_due;
                    $vatRateValue = (float) ($vatRate->rate ?? 0);
                    $vatMultiplier = $vatRateValue > 0
                        ? (1 + ($vatRateValue / 100))
                        : (1 + (float) ($vatRate->multiplier ?? 0));
                    $amountExVat = $vatMultiplier > 0 ? round($totalDue / $vatMultiplier, 2) : $totalDue;
                    $vatAmount = round($totalDue - $amountExVat, 2);

                    $payment = BookingPayment::updateOrCreate(
                        ['booking_id' => $booking->id,],
                        [
                            'type_pay_option_id'   => 2,     // or 'Pay Offline'
                            'type_payment_id' => 3, // credit card
                            'status_payment_id' => 2,  // 'paid', 'failed', 'cancelled'
                            'ex_vat_amount'         => $amountExVat, // total excl VAT
                            'lookup_item_id' => $vatRate->id,
                            'vat' => $vatRate->rate,
                            'vat_amount' => $vatAmount,
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

                    AuditLog::create([
                        'description' => 'Payment recorded (simulated)',
                        'subject_id' => $booking->id,
                        'subject_type' => Booking::class,
                        'user_id' => null,
                        'properties' => json_encode([
                            'payment_id' => $payment?->id,
                            'amount' => $booking->grand_tot_due,
                            'method' => 'simulated',
                        ]),
                        'host' => request()->ip(),
                    ]);

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
                    $invoiceTypeId = TypeInvoice::where('slug', 'std')->value('id');
                    $invoice = Invoice::where('booking_id', $booking->id)
                        ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
                        ->first();
                    $customer = Customer::where('id', $booking->customer_id)->first();
                    $site = Team::findorFail($invoice->team_id);
                    $items = $booking?->booking_items->first();

                    $subject = 'Payment Confirmation';
                    $recipient = $customer->email_address;

                    // 🔹 Load Blade view with data
                    $data = $renderService->invoice($booking, $customer, $invoice);
                    $invoicePdf = Pdf::loadView('invoices.invoice', $data);
                    // Optional: tweak DOMPDF options
                    $invoicePdf->setPaper('A4', 'portrait');


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
                    AuditLog::create([
                        'description' => 'Email sent (invoice payment)',
                        'subject_id' => $booking->id,
                        'subject_type' => Booking::class,
                        'user_id' => null,
                        'properties' => json_encode([
                            'invoice_id' => $invoice->id,
                            'receiver' => $customer->email_address,
                            'subject' => 'Invoice ' . $data['invoice']['number'],
                            'attachment' => $invoiceFilename,
                        ]),
                        'host' => request()->ip(),
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
                    $bookingItem = BookingItem::with('booking', 'booking.team', 'booking.team.file_logo', 'customers', 'booking_inventory.inventory_time_slot', 'booking_inventory.inventory_type_day.type_day', 'booking_fee')
                        ->where('booking_id', $booking->id)->first(); // ->get();
                    $data = $this->getPassDetails($bookingItem);
                    // dd($data);
                    $html = view('pdfs.boarding-pass', ['data' => $data])->render();
                    $dir = "pass/{$bookingItem->id}";
                    $fileName = "{$dir}/boarding_pass_{$bookingItem->id}.pdf";
                    $disk = Storage::disk('public');
                    $fullPath = $disk->path($fileName);
                    if (!$disk->exists($fileName)) {
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
                    }

                    // return response()->download($fullPath);
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Your Boarding Pass – Invoice ',
                        'body'           => strip_tags($content), // or a summary
                        'attachment_path' => $fileName,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => null,
                        'auditable_type' => BookingItem::class,
                        'auditable_id'   => $bookingItem->id,
                        'team_id'        => $bookingItem->team_id,
                    ]);
                    AuditLog::create([
                        'description' => 'Email sent (boarding pass payment)',
                        'subject_id' => $booking->id,
                        'subject_type' => Booking::class,
                        'user_id' => null,
                        'properties' => json_encode([
                            'booking_item_id' => $bookingItem->id,
                            'receiver' => $customer->email_address,
                            'attachment' => $fileName,
                        ]),
                        'host' => request()->ip(),
                    ]);
                    File::create([
                        'team_id'      => $bookingItem->team_id,
                        'table_name'   => 'BookingItem',
                        'table_row_id' => $bookingItem->id,
                        'file_name'    =>  $fileName,
                        'file_type'    => "application/pdf",
                        'type_file_id' => 6, // optional enum/type
                        'path'         => $fileName,
                        'booking_id'   => $booking->id,
                    ]);
                    $attachments = [
                        [
                            'data' => $invoicePdf->output(),
                            'name' => "invoice.pdf",
                            'mime' => 'application/pdf',
                        ],
                        [
                            'data' => file_get_contents($fullPath),
                            'name' => $fileName,
                            'mime' => 'application/pdf',
                        ],
                    ];
                    // //send mail
                    $messageData = [
                        'invoice' => [
                            'number' => $booking->reference ?? 'INV-No',
                            'date' => now()->format('Y-m-d'),
                        ],
                        'customer' => [
                            'name' => $customer->full_name,
                        ],
                        'booking' => [
                            'reference' => $booking->reference,
                        ],
                    ];

                    Mail::to($customer->email_address)->send(new BookingMail($messageData, $attachments));

                    Log::info('Payment confirmation email sent', [
                        'booking_id' => $booking->id,
                        'customer' => $customer->email_address,
                    ]);

                    return $booking;
                });

                $vatRate = LookupItem::findDefaultForTeam('vat_rate', $booking->team_id);

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
                return redirect()->back()->with('error', 'Something went wrong while saving your booking. Please try again.');
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
            AuditLog::create([
                'description' => 'Payment intent recorded (offline)',
                'subject_id' => $booking->id,
                'subject_type' => Booking::class,
                'user_id' => null,
                'properties' => json_encode([
                    'payment_id' => $payment?->id,
                    'amount' => $booking->grand_tot_due,
                    'method' => 'offline',
                    'proof' => $filePath,
                ]),
                'host' => request()->ip(),
            ]);
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
        $renderService = app(RendererService::class);
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


        // 🔹 Load Blade view with data
        $invoiceTypeId = TypeInvoice::where('slug', 'std')->value('id');
        $invoice = Invoice::where('booking_id', $booking->id)
            ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
            ->first();
        $data = $renderService->invoice($booking, $customer, $invoice);
        $invoicePdf = Pdf::loadView('invoices.invoice', $data);

        // Optional: tweak DOMPDF options
        $invoicePdf->setPaper('A4', 'portrait');

        // 🔹 Download or stream PDF
        //return $pdf->download('invoice-' . $data['invoice']['number'] . '.pdf');
        return $invoicePdf->stream(); // to preview in browser

        // $html = $this->renderTemplate($template->content, $data);

        // $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        // return $pdf->stream("invoice-{$booking->id}.pdf");
        // or ->download("invoice-{$invoice->id}.pdf");
    }

    private function renderTemplate(string $template, array $data): string
    {
        // https://canopy-tours.inspireonline.co.za/images/logo.png
        // /storage/" . $data['file_logo'] . "
        $logo = "<img src='logo.png' alt='Logo' style='max-height:50px;'/>";
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
            '{{vat_amount}}'          => $data['vat_amount'] ?? '',
            '{{amount_paid}}'         => $data['amount_paid'] ?? '',
            '{{balance_due}}'         => $data['vat_amount'] - $data['amount_paid'],
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
        // $booking = Booking::with('team.file_logo', 'booking_items', 'booking_items.customers', 'booking_items.booking_inventory', 'booking_items.booking_fee')->find($booking->id);
        // there could be multiple bookin items per booking for now simplify to one
        $bookingItem = BookingItem::with('booking', 'booking.team', 'booking.team.file_logo', 'customers', 'booking_inventory.inventory_time_slot', 'booking_inventory.inventory_type_day.type_day', 'booking_fee')
            ->where('booking_id', $booking->id)->first(); // ->get();
        $data = $this->getPassDetails($bookingItem);
        // dd($data);
        $html = view('pdfs.boarding-pass', ['data' => $data])->render();

        $dir = "pass/{$bookingItem->id}";
        $random = rand(1, 100);
        $file = "{$dir}/boarding_pass_{$random}{$bookingItem->id}.pdf";
        $disk = Storage::disk('public');
        $fullPath = $disk->path($file);
        Log::info('boarding pass ' . var_export($fullPath, true));
        Log::info('boarding pass ' . var_export($file, true));
        Log::info('boarding pass ' . var_export($dir, true));

        if (!$disk->exists($file)) {
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
        }

        return response()->download($fullPath);

        // $pdf = Pdf::loadView('pdfs.boarding-pass', compact('data'))
        //     ->setPaper('a4', 'portrait');

        // return $pdf->download('boarding-pass.pdf');
    }

    public function getPassDetails($bookingItem)
    {
        $seatsBooked = $bookingItem?->booking_inventory?->pax ?? 0;
        $basePassData = [
            'flightName' => $bookingItem->booking->team->name,
            'location' => optional($bookingItem->service)->name ?? $bookingItem->booking->team->name,
            'address' => '',
            'passenger' => $bookingItem->booking->lead_name,
            'date' => $bookingItem->booking_inventory->date,
            'time' => optional($bookingItem->booking_inventory->inventory_time_slot)->start_time,
            'seat' => $bookingItem->booking->reference,
            'seats_booked' => $seatsBooked,
            'boarding_time' => optional($bookingItem->booking_inventory->inventory_time_slot)->start_time,
            'booking_no' => $bookingItem->booking->reference,
            'flight' => $bookingItem->booking->team->name,
            'passenger_name' => $bookingItem->booking->lead_name,
            'footer' => '',
            'sections' => [],
        ];

        $teamId = $bookingItem->booking->team_id;
        $serviceId = $bookingItem->service_id;
        $seasonId = $bookingItem->season_id;

        $template = null;
        if ($teamId) {
            $template = BoardingPassTemplate::with(['sections' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
                ->where('team_id', $teamId)
                ->where('is_active', true)
                ->when($serviceId, fn($q) => $q->where(function ($qq) use ($serviceId) {
                    $qq->whereNull('service_id')->orWhere('service_id', $serviceId);
                }))
                ->when($seasonId, fn($q) => $q->where(function ($qq) use ($seasonId) {
                    $qq->whereNull('season_id')->orWhere('season_id', $seasonId);
                }))
                ->orderByRaw('season_id is null')
                ->orderByRaw('service_id is null')
                ->first();

            if (!$template && !$serviceId && !$seasonId) {
                $template = BoardingPassTemplate::with(['sections' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                    ->where('team_id', $teamId)
                    ->where('is_active', true)
                    ->whereNull('service_id')
                    ->whereNull('season_id')
                    ->where('is_default', true)
                    ->first();
            }

            if (!$template && !$serviceId && !$seasonId) {
                $this->alertMissingBoardingPassTemplate($teamId, $bookingItem);
            }
        }

        if ($template) {
            $sections = $template->sections
                ->map(function ($section) {
                    return [
                        'title' => $section->title,
                        'icon' => $section->icon_path,
                        'items' => is_array($section->items) ? $section->items : [],
                    ];
                })
                ->values()
                ->all();

            return array_merge($basePassData, [
                'location' => $template->location ?? $basePassData['location'],
                'address' => $template->address ?? '',
                'footer' => $template->footer ?? '',
                'sections' => $sections,
            ]);
        }

        //dd($bookingItem->booking->team->name);
        switch ($bookingItem->booking->team->name) {
            case 'Karkloof':
                return [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Karkloof Canopy Tour',
                    'address' => 'Portion 29 Farm Welgevonden 969, Karkloof Road, Howick',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' =>  $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'seats_booked' => $seatsBooked,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'footer' => 'All visitors and participants and participating in any activities and/or transport offered or arranged by Canopy Tours or third parties engaged by Canopy Tours by their signature of this document acknowledge that they have read & understand this indemnity, disclaimer and waiver (‘the Indemnity’) and agree to be bound by it, failing which he/she can choose not to board the transport and/or not to participate in the activities.',
                    'sections' => [

                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A short 4x4 trip to the drop off point.',
                                'A 1/2 hour fully guided zipline adventure along 12 elevated platforms, along 10 ziplines',
                                'Small exclusive groups of 8–10 people.',
                                '2x fully trained Karkloof Canopy Tour facilitators will join you on each tour.'
                            ]
                        ],

                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at 20 min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'Once the safety briefing begins late guests cannot join and the tour will be considered missed.'
                            ]
                        ],

                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Rain jacket or rain poncho on rainy days.',
                                'A small pouch or small sling bag for your phone, should you want to take photos.'
                            ]
                        ],

                        [
                            'title' => 'CBD Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'If booked, be ready at your transfer departure time.'
                            ]
                        ],

                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are still run in the rain and will only be stopped/cancelled if lightning in the near vicinity.',
                                'Notify the team 5 days prior to your tour if you want to move or cancel your tour.'
                            ]
                        ],

                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 120 kg per person.',
                                'Limit is based on harness size and strap circumference (size 44 pants is a good indication)',
                                'If over 120 kg but fit your body shape may still allow safe harness fit.',
                                'Minimum age: 6 years old.'
                            ]
                        ],

                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer guests to go tandem with one of our qualified guides.',
                                'Minimum age: 6 years old.'
                            ]
                        ],

                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'Small coffee shop onsite.',
                                'Wi-Fi available at the base.'
                            ]
                        ],

                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                '100% refund for cancellations 5+ days before the tour.',
                                'No refund for late arrivals or missed tours or if a guest starts a tour and does not finish.',
                                'If the company cancels you receive a full refund.',
                                'Third-party refunds handled through the agent.'
                            ]
                        ]
                    ]
                ];
                break;

            case 'Tsitsikamma':
                return [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Tsitsikamma Canopy Tour',
                    'address' => '101 Darnell Street, Stormsriver Village, Tsitsikamma, Garden Route, Eastern Cape, 6308',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' =>  $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'seats_booked' => $seatsBooked,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'footer' => 'All visitors and participants using any activities and/or transport offered or arranged by Stormsriver Adventures Pty Ltd (Tsitsikamma Canopy Tour), including services by third parties, acknowledge by signing this document that they have read, understood, and agree to this indemnity, disclaimer, and waiver (“the Indemnity”). By signing, participants indemnify and hold harmless Stormsriver Adventures Pty Ltd, its directors, employees, guides, agents, contractors, and affiliates against any claims, loss, injury, damage, or liability arising from participation. Participants who do not agree may choose not to participate.',

                    'sections' => [

                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A return 4x4 experience from the adventure centre to the forest.',
                                'A 2 – 3 hour fully guided forest canopy tour zipline experience along 10 elevated platforms.',
                                'Small exclusive groups of 8 people.',
                                'Two fully trained Tsitsikamma Canopy Tour guides will join you on each tour.',
                                'A personalized video (this is available to purchase as an optional extra).'
                            ]
                        ],

                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 10–15min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'If you are coming from outside the Tsitsikamma area, please factor in a 20-30 min delay on the time indicated by Google Maps due to irregular traffic conditions and possible roadworks.',
                                'Once the safety briefing begins late guests cannot join and the tour will be considered missed.'
                            ]
                        ],

                        [
                            'title' => 'What to Bring/Note',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'The activity operates in all-weather conditions. Even in the rain – just bring something warm to wear and come and enjoy a rainforest experience with us! It is in the Tsitsikamma Indigenous Forest, and always a magical experience. A rain jacket will be supplied if need be.',
                                'Comfortable clothes should be worn and any footwear that is secure on your feet and that will not fall if you are suspended in mid-air. (Closed shoes).',
                                'Long/Braided or Styled Hair: Please note that a helmet will have to fit onto your head. Hair cannot be tied on top of your head, obstructing the helmet from comfortably protecting your head.',
                                'If a device is taken on tour and falls while on the system, it cannot be retrieved.'
                            ]
                        ],

                        [
                            'title' => 'Accommodation & Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'We have a network of accommodation options and transfer operators we can recommend for use. Please reach out to our Information Centre Officers should you require assistance with arrangements on +27767313156 (WhatsApp’s welcome).',
                                'Transfers are operated by an external supplier.',
                                'For assistance with additional travel arrangements, email info2@stormsriver.com'
                            ]
                        ],

                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'We operate in all weather conditions. For safety reasons, we will cancel the tour only if severe or unsafe weather develops in the area.',
                                'We monitor the weather closely and may cancel, refund, or reschedule your tour if necessary. Any changes will be discussed with you beforehand.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],

                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 100–110 kg per person.',
                                'Limit is based on harness size and strap circumference.',
                                'For safety and comfort, limits are based on harness size and strap circumference, not body weight.',
                                'Our focus is on safely fitting you into the harness rather than the number on the scale. If you’re over 120–130 kg and your body shape fits the harness comfortably, you’re welcome to join the tour.'
                            ]
                        ],

                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer 2 tandems per tour.',
                                'Please email us at adventure@gardenroute.co.za should you or anyone in your group require a tandem.',
                                'Minimum age: 5 years old.',
                                'Children ages with us are 5 – 17 years old.',
                                'Tandems are only allowed with our guides. The first three slides help you get comfortable. If a tandem is needed, please speak to your guide.'
                            ]
                        ],

                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'You’ll receive a bottle of water during the tour to keep you refreshed. Once you’re back at the office, we’ll hand you your certificate and a meal discount voucher to enjoy at selected cafés in Storms River Village.',
                                'Wi-Fi available at the base.'
                            ]
                        ],

                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                'A 10% admin fee will be charged on all refunds processed.',
                                'Bookings cancelled 24-hours before the scheduled tour time will be refunded in full (less admin fee).',
                                'Request for refunds, cancellations, or changes to reservations made through third parties must be handled through them.',
                                'No refunds on cancellation on the day'
                            ]
                        ],
                    ]
                ];
                break;

            case 'Drakensberg':
                return [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Drakensberg Canopy Tour',
                    'address' => 'Drakensberg Sun Property, R160 off the R600, Cathkin Park Central Drakensberg, 3340',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' =>  $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'seats_booked' => $seatsBooked,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'footer' => 'Please note:  A Canopy Tour is an adventure activity and does come with risks. Although every effort is taken by the Canopy Tours and our staff to ensure your safety, we request that you please follow all instructions given to you by our staff either in the briefing or on the system itself.  Any non compliance to safety and or rules and polocies may result in a person being removed from the tour at anytime, at which time no refund will be given.',
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A 2,5km 4x4 drive, from base to the start point and from our finish point back to base.',
                                'A 2.5 - 3 hour fully guided forest canopy zipline adventure along 11 elevated ziplines.',
                                'Groups of up to 10 people at a time, however bigger groups can be accommidated by arrangment.',
                                '2x fully trained Drakensberg Canopy Tour facilitators will join you on each tour to ensure your safe and well looked after.',
                                'A bottle of water and a KitKat to keep you hydrated and your energy levels up.'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 20 - 30min BEFORE your tour time.',
                                'Please ensure you arrive by the requested time, as late guests may be disappointed when we are unable to accommidate them.',
                                'Once the safety briefing begins late guests might not be able to join and the tour could be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Comfortable, weather appropiate clothing and closed walking shoes.',
                                'Rain jacket on rainy or potentially rainy days.',
                                'You may bring your phone (at own risk). Please ensure you have zip up pockets or ask at reception for options.'
                            ]
                        ],
                        [
                            'title' => 'CBD Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                ''
                            ]
                        ],
                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are mildly weather-dependent but operate in rain or shine.',
                                'The weather is monitored closely — tours may be postponed or cancelled due to severe weather and specifically if there is litening in the emediate area.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 120 -130kg kg per person.',
                                'Limit is based on harness size and strap circumference. (Mens size 44 jean should fit)',
                                'If over 120–130 kg but fit your body shape may still allow safe harness fit.',
                                'Minimum age: 6 years old. Management reserves the right to decliner a child which they think, for safety reasons should not do the Canopy Tour, regardless of age.',
                                'Anyone under the influence or suspected to be under the influence of alcohol are not allowed to participate in a Canopy Tour.  (Management reserves the right to breathalize suspected guests.',
                                'Pregnant or suspected pregnant women are not allowed to do a Canopy Tour.'
                            ]
                        ],
                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                '',
                            ]
                        ],
                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Extra snacks and drinks available before and after your tour.',
                                'We accept card and cash payments on site.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                'NO REFUNDS WILL BE GIVEN FOR THE FOLLOWING REASONS:',
                                '- No-Shows',
                                '- Not completing the tour or deciding last minute not to zipline',
                                '- Any cancellations made within 24 hours of the tour',
                                '- Late arrivals',
                                '- Postponements within 24 hours of the tour'
                            ]
                        ],
                    ]
                ];
                break;
            case 'Test Team':
                return [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Cape Canopy Tour',
                    'address' => 'Address Placeholder',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' =>  $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'seats_booked' => $seatsBooked,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A return 4x4 experience from through the nature reserve.',
                                'A 4–5 hour fully guided mountain top zipline adventure along 13 elevated platforms.',
                                'Small exclusive groups of 8–9 people.',
                                '2x fully trained Cape Canopy Tour facilitators will join you on each tour.',
                                'A personalized video (this is available to purchase as an optional extra).'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 10–15min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'If coming from Cape Town, please factor in an extra 30–45min for irregular traffic delays.',
                                'Once the safety briefing begins, late guests cannot join and the tour will be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Warm clothes — mountain weather is unpredictable.',
                                'Sunblock & Hat; rain jacket on rainy days.',
                                'A small light backpack (optional).',
                                'You may bring your phone or camera (at own risk).'
                            ]
                        ],
                        [
                            'title' => 'CBD Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'If booked, be ready at your transfer departure time.',
                                'Transfers are operated by an external supplier.',
                                'Contact us if you have not been contacted by the transfer driver.'
                            ]
                        ],
                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are mildly weather-dependent but operate in most conditions.',
                                'The weather is monitored closely — tours may be cancelled, refunded, or rescheduled.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 100–110 kg per person.',
                                'Limit is based on harness size and strap circumference.',
                                'If over 100–110 kg but fit, your body shape may still allow safe harness fit.',
                                'Minimum age: 5 years old.'
                            ]
                        ],
                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer 2 tandems per tour.',
                                'Tandems for guests under 50 kg only.',
                                'Email: zipline@capecanopytour.co.za for children under 9 years.',
                                'Minimum age: 5 years old.',
                                'Children under 5 may need to ride tandem with a guide.'
                            ]
                        ],
                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'Extra snacks and drinks available before and after your tour.',
                                'Wi-Fi available at the base.',
                                'Bring a card — no cash accepted.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                '100% refund for cancellations 24+ hours before the tour.',
                                'No refund for late arrivals or missed tours.',
                                'If the company cancels, you receive a full refund.',
                                'Third-party refunds handled through the agent.'
                            ]
                        ],
                    ]
                ];
                break;
            default:
                return $basePassData;
                break;
        }
    }

    protected function alertMissingBoardingPassTemplate(int $teamId, BookingItem $bookingItem): void
    {
        $typeAlert = TypeAlert::firstOrCreate(
            ['slug' => 'boarding_pass_template_missing'],
            ['name' => 'Boarding Pass Template Missing']
        );

        $alertText = sprintf(
            'Boarding pass template missing (team %d, booking %d, item %d).',
            $teamId,
            $bookingItem->booking_id ?? 0,
            $bookingItem->id ?? 0
        );

        $alreadyAlerted = Alert::where('team_id', $teamId)
            ->where('type_alert_id', $typeAlert->id)
            ->where('alert_text', $alertText)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyAlerted) {
            return;
        }

        $alert = Alert::create([
            'alert_text' => $alertText,
            'alert_link' => route('admin.boardingpass.index'),
            'type_alert_id' => $typeAlert->id,
            'team_id' => $teamId,
        ]);

        $users = User::where('team_id', $teamId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Manager', 'Consultant']);
            })
            ->with('roles')
            ->get();

        foreach ($users as $user) {
            $user->alerts()->attach($alert->id);
        }
    }

    public function invoiceDownload1(Booking $booking)
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
            'amount_ex_vat' => round(($booking->grand_tot_due / 1.15), 2),
            'vat'           => round(($booking->grand_tot_due - ($booking->grand_tot_due / 1.15)), 2),
            'vat_amount'    => $booking->grand_tot_due,
            'amount_paid'   => ($booking->tot_paid ?? 0) * 1.15,
            'balance_due'   => $booking->grand_tot_due - (($booking->tot_paid ?? 0) * 1.15),
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
    public function detailDownloadOld(Booking $booking)
    {
        dd($booking);
        $sessionId = session()->getId() ?? null;
        $renderService = app(RendererService::class);
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

        $items = $renderService->voucher($booking, $bookingVoucher);
        $random = rand(1, 100);
        $pdf = Pdf::loadView('emails.booking_voucher', compact('items'));

        return $pdf->stream('booking-details.pdf');
    }
}
