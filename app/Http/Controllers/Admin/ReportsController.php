<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingQuestionnaire;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Minimum report requirements from my side  - all available in Excel/CSV format

        // Payments Report

        // Selection Fields
        // Payment Date (From To)
        // Payment Type/ Method
        // Activity/Service
        // Report Fields
        // Booking Number/Identifier
        // Booking Status (e.g. Active or Cancelled)
        // Booking Source (e.g. online or manual)
        // Customer Name (who booked)
        // Booking Date
        // Activity Date
        // Payment Method
        // Payment Date
        // Payment Value/ Amount

        // Bookings Report

        // Selection Fields
        // Activity Date (From To)
        // Activity/Service
        // Report Fields
        // Booking Number/ Identifier
        // Booking Status (e.g. Active or Cancelled)
        // Booking Source (e.g. online or manual)
        // Customer Name (who booked)
        // Booking Date
        // Activity Date
        // Agent/User (who made the booking)
        // Activity/Service
        // Pax Adults
        // Pax Children
        // Pax Guides
        // Total Value/ Amount
        // Value/Amount Paid
        // Fully Paid Indicator (Y/N)
        // Booking Note

        // Check-In Report/ Manifest

        // Selection Fields
        // Activity Date (From To)
        // Activity/Service
        // You could allow the inclusion of various fields here from the Manifest by a checkbox (see Activitar Passenger List Report). For e.g. Group Tye, Nationality or Meal
        // Report Fields
        // Booking Number/ Identifier
        // Activity/Service
        // Activity Date
        // Time slot/ Activity Time
        // Customer Name (who booked)
        // Customer Name (who checked in) – will be blank if no check in
        // Checked Indicator (Y/N)
        // Arrived Indicator (Y/N) – as discussed yesterday, this would be a field on the booking that the Front Desk would check to say the client actually arrived. If clients check in off site, the info here will exist but this does not mean they actually arrived.
        // Additional fields as selected
        // Booking Note

        // Bookings Report

        // Total Value – value before pricing adjustment
        // Pricing Adjustment – the value of the pricing adjustment on that booking (discounts or promotions)
        // Net Value – value after the pricing adjustment

        return Inertia::render('Admin/Reports/Index', [
            'title' => 'All',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingQuestionnaire $bookingQuestionnaire)
    {
        //
    }
}
