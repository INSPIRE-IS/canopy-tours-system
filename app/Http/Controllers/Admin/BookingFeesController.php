<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingFeeRequest;
use App\Http\Requests\StoreBookingFeeRequest;
use App\Http\Requests\UpdateBookingFeeRequest;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingFeesController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('booking_fee_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingFees = BookingFee::with(['booking', 'booking_detail', 'team'])->get();

        return view('admin.bookingFees.index', compact('bookingFees'));
    }

    public function create()
    {
        abort_if(Gate::denies('booking_fee_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookings = Booking::pluck('lead_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $booking_details = BookingDetail::pluck('is_override_time_slots', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.bookingFees.create', compact('booking_details', 'bookings'));
    }

    public function store(StoreBookingFeeRequest $request)
    {
        $bookingFee = BookingFee::create($request->all());

        return redirect()->route('admin.booking-fees.index');
    }

    public function edit(BookingFee $bookingFee)
    {
        abort_if(Gate::denies('booking_fee_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookings = Booking::pluck('lead_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $booking_details = BookingDetail::pluck('is_override_time_slots', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bookingFee->load('booking', 'booking_detail', 'team');

        return view('admin.bookingFees.edit', compact('bookingFee', 'booking_details', 'bookings'));
    }

    public function update(UpdateBookingFeeRequest $request, BookingFee $bookingFee)
    {
        $bookingFee->update($request->all());

        return redirect()->route('admin.booking-fees.index');
    }

    public function show(BookingFee $bookingFee)
    {
        abort_if(Gate::denies('booking_fee_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingFee->load('booking', 'booking_detail', 'team');

        return view('admin.bookingFees.show', compact('bookingFee'));
    }

    public function destroy(BookingFee $bookingFee)
    {
        abort_if(Gate::denies('booking_fee_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingFee->delete();

        return back();
    }
}
