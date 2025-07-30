<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingDetailRequest;
use App\Http\Requests\StoreBookingDetailRequest;
use App\Http\Requests\UpdateBookingDetailRequest;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Season;
use App\Models\Service;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingDetailsController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('booking_detail_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingDetails = BookingDetail::with(['booking', 'service', 'season', 'team'])->get();

        return view('admin.bookingDetails.index', compact('bookingDetails'));
    }

    public function create()
    {
        abort_if(Gate::denies('booking_detail_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookings = Booking::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.bookingDetails.create', compact('bookings', 'seasons', 'services'));
    }

    public function store(StoreBookingDetailRequest $request)
    {
        $bookingDetail = BookingDetail::create($request->all());

        return redirect()->route('admin.booking-details.index');
    }

    public function edit(BookingDetail $bookingDetail)
    {
        abort_if(Gate::denies('booking_detail_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookings = Booking::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bookingDetail->load('booking', 'service', 'season', 'team');

        return view('admin.bookingDetails.edit', compact('bookingDetail', 'bookings', 'seasons', 'services'));
    }

    public function update(UpdateBookingDetailRequest $request, BookingDetail $bookingDetail)
    {
        $bookingDetail->update($request->all());

        return redirect()->route('admin.booking-details.index');
    }

    public function show(BookingDetail $bookingDetail)
    {
        abort_if(Gate::denies('booking_detail_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingDetail->load('booking', 'service', 'season', 'team');

        return view('admin.bookingDetails.show', compact('bookingDetail'));
    }

    public function destroy(BookingDetail $bookingDetail)
    {
        abort_if(Gate::denies('booking_detail_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingDetail->delete();

        return back();
    }
}
