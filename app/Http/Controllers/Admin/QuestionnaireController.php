<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Quesionnaire;
use App\Models\State;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Questionnaire-Welcome');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //lead guides

        //safety guides

        //grpup type





        $bookingId = request()->route('bookingId');
        return Inertia::render('Questionnaire', ['bookingId' => $bookingId]);
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
    public function show(Quesionnaire $quesionnaire)
    {
        $bookingId = request()->route('bookingId');
        return Inertia::render('Questionnaire', ['bookingId' => $bookingId]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quesionnaire $quesionnaire)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quesionnaire $quesionnaire)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quesionnaire $quesionnaire)
    {
        //
    }


    public function getContinents()
    {
        return Continent::orderBy('name')->get();
    }

    public function getCountries($continentId)
    {
        return Country::where('continent_id', $continentId)->orderBy('name')->get();
    }

    public function getStates($countryId)
    {
        return State::where('country_id', $countryId)->orderBy('name')->get();
    }
}
