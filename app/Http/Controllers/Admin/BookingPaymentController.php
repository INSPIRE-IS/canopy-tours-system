<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingPaymentRequest;
use App\Http\Requests\StoreBookingPaymentRequest;
use App\Http\Requests\UpdateBookingPaymentRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\Gateway;
use App\Models\LookupItem;
use App\Models\StatusPayment;
use App\Models\TypePayment;
use App\Models\TypePayOption;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

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
            //$booking = DB::transaction(function () use ($validated) {
            //$payment = $request->all();
            $validated['status_payment_id'] = 2; // paid
            $validated['gateway_id'] = 2; // manual
            logger()->info($validated);
            $bookingPayment = BookingPayment::create($validated);
            $booking = $bookingPayment->booking;
            logger()->info($booking);
            $booking->update([
                'status_payment_id' => 2, // paid
                'tot_paid' => $bookingPayment->ex_vat_amount,
            ]);
            //TODO update booking item with payment amount if this is a multiple booking activity
            $booking->booking_items->each(function ($item) use ($bookingPayment) {
                $item->update([
                    'tot_paid' => $bookingPayment->ex_vat_amount,
                ]);
            });

            Log::info('booking updated', [
                'booking' => $booking,
            ]);
            return redirect()->route('admin.bookings.index');

            //     return $booking;
            // });
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
            // return response()->json([
            //     'success' => false,
            //     'error' => 'Something went wrong while saving your booking. Please try again.',
            // ], 500);
        }
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
