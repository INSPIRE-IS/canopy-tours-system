<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingInventoryRequest;
use App\Http\Requests\StoreBookingInventoryRequest;
use App\Http\Requests\UpdateBookingInventoryRequest;
use App\Models\BookingDetail;
use App\Models\BookingInventory;
use App\Models\Season;
use App\Models\Service;
use App\Models\TypeDay;
use App\Models\TypeTimeSlot;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BookingInventoryController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('booking_inventory_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingInventories = BookingInventory::with(['service', 'season', 'booking_detail', 'day', 'time_slot'])->get();

        return view('admin.bookingInventories.index', compact('bookingInventories'));
    }

    public function create()
    {
        abort_if(Gate::denies('booking_inventory_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $booking_details = BookingDetail::pluck('is_override_time_slots', 'id')->prepend(trans('global.pleaseSelect'), '');

        $days = TypeDay::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $time_slots = TypeTimeSlot::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.bookingInventories.create', compact('booking_details', 'days', 'seasons', 'services', 'time_slots'));
    }

    public function store(StoreBookingInventoryRequest $request)
    {
        $bookingInventory = BookingInventory::create($request->all());

        return redirect()->route('admin.booking-inventories.index');
    }

    public function edit(BookingInventory $bookingInventory)
    {
        abort_if(Gate::denies('booking_inventory_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $booking_details = BookingDetail::pluck('is_override_time_slots', 'id')->prepend(trans('global.pleaseSelect'), '');

        $days = TypeDay::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $time_slots = TypeTimeSlot::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bookingInventory->load('service', 'season', 'booking_detail', 'day', 'time_slot');

        return view('admin.bookingInventories.edit', compact('bookingInventory', 'booking_details', 'days', 'seasons', 'services', 'time_slots'));
    }

    public function update(UpdateBookingInventoryRequest $request, BookingInventory $bookingInventory)
    {
        $bookingInventory->update($request->all());

        return redirect()->route('admin.booking-inventories.index');
    }

    public function show(BookingInventory $bookingInventory)
    {
        abort_if(Gate::denies('booking_inventory_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingInventory->load('service', 'season', 'booking_detail', 'day', 'time_slot');

        return view('admin.bookingInventories.show', compact('bookingInventory'));
    }

    public function destroy(BookingInventory $bookingInventory)
    {
        abort_if(Gate::denies('booking_inventory_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bookingInventory->delete();

        return back();
    }
}
