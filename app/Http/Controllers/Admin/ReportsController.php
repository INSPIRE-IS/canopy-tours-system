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
