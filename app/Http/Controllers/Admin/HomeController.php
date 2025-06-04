<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        return Inertia::render('Home');
        // return view('home');
    }



    public function bookings()
    {
        $bookings = [
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '10:00 - 12:00',
                'pax' => 3,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 0,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
            [
                'time' => '09:00 - 11:30',
                'pax' => 6,
                'maxPax' => 8,
                'service' => 'Tsitsikamma Canopy Tour',
                'price' => 5570,
                'paid' => 5570,
                'due' => 3780,
                'dueStatus' => 'Overdue', // Could be 'Normal', 'Overdue', or 'Paid'
            ],
        ];

        return Inertia::render('Admin/Bookings', ['bookings' => $bookings]);
    }
}
