<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingPaymentRequest;
use App\Http\Requests\StoreBookingPaymentRequest;
use App\Http\Requests\UpdateBookingPaymentRequest;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Gateway;
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

        return Inertia::render('Admin/BookingPayments/Create', [
            'booking' => $booking,
            'gateways' => $gateways,
            'status_payments' => $status_payments,
            'type_pay_options' => $type_pay_options,
            'type_payments' => $type_payments,
            'updated_bies' => $updated_bies,
            'mode' => 'create',

        ]);
    }

    public function store(StoreBookingPaymentRequest $request)
    {
        dd($request->all());
        $bookingPayment = BookingPayment::create($request->all());

        return redirect()->route('admin.booking.index');
    }

    public function edit(BookingPayment $bookingPayment)
    {
        //dd($bookingPayment->booking);
        abort_if(Gate::denies('booking_payments_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //$booking = Booking::findOrFail($request->booking_id);
        $bookingPayment->load('booking', 'type_pay_option', 'type_payment', 'gateway', 'status_payment', 'updated_by', 'team');

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

        return Inertia::render('Admin/BookingPayments/Edit', [
            'booking' => $bookingPayment,
            'gateways' => $gateways,
            'status_payments' => $status_payments,
            'type_pay_options' => $type_pay_options,
            'type_payments' => $type_payments,
            'updated_bies' => $updated_bies,

        ]);

        $bookings = Booking::pluck('lead_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $type_pay_options = TypePayOption::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $type_payments = TypePayment::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $gateways = Gateway::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $status_payments = StatusPayment::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bookingPayment->load('booking', 'type_pay_option', 'type_payment', 'gateway', 'status_payment', 'updated_by', 'team');

        return view('admin.bookingPayments.edit', compact('bookingPayment', 'bookings', 'gateways', 'status_payments', 'type_pay_options', 'type_payments', 'updated_bies'));
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
