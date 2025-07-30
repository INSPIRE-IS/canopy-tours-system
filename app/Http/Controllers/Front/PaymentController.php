<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Str;

class PaymentController extends Controller
{
    public function review(Booking $booking)
    {
        return Inertia::render('ReviewPayment', [
            'booking' => $booking,
        ]);
    }

    public function simulated(Request $request)
    {
        $booking = Booking::findOrFail($request->booking);

        // Mark as paid (in real app, you'd validate PayFast response)
        $booking->update([
            'status_booking_id' => 2, // e.g., "paid"
            'tot_paid' => $booking->grand_tot_due,
        ]);

        $payment = BookingPayment::updateOrCreate(
            ['booking_id' => $booking->id,],
            [
                'type_pay_option_id'   => 2,     // or 'Pay Offline'
                'type_payment_id' => 3, // credit card
                'status_payment_id'         => 1,        // 'paid', 'failed', 'cancelled'
                'amount'         => $booking->grand_tot_due,          // full amount due or partial
                'reference'      => $booking->reference, // Str::uuid(),      // can be order ref or external tx
                'gateway_id'        => 1,        // or 'Manual', 'Stripe', etc.
                'meta'           => json_encode(['ip' => request()->ip()]),
                'payment_date'   =>  now(),
                'redeemer_name'  => '', //$validated['name'] . ' ' . $validated['surname'],
                'voucher_code' => null,
                'note'           => '',
                'updated_by_id'  => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ]
        );

        return Inertia::render('SimulatedPayment', [
            'booking' => $booking,
        ]);
    }

    public function offline(Request $request)
    {
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
    }
}
