<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $settings1 = [
            'chart_title'           => 'Monthly Bookings',
            'chart_type'            => 'line',
            'report_type'           => 'group_by_date',
            'model'                 => 'App\Models\Booking',
            'group_by_field'        => 'created_at',
            'group_by_period'       => 'month',
            'aggregate_function'    => 'count',
            'filter_field'          => 'created_at',
            'filter_period'         => 'year',
        ];

        $chart1 = new LaravelChart($settings1);

        $labels = range(1, 12);
        $labels = array_map(fn($m) => str_pad($m, 2, '0', STR_PAD_LEFT), $labels); // ["01", "02", ... "12"]

        // $rawData is your dataset from LaravelChart
        $rawData = $chart1->getDatasets()[0]['data']; // This is a Collection with keys like "2025-08" => count

        // Map labels to values (or 0 if no data)
        $data = [];
        foreach ($labels as $month) {
            // Find value for this month in $rawData keys, matching by suffix "-$month"
            $value = 0;
            foreach ($rawData as $key => $val) {
                // Example key: "2025-08"
                if (str_ends_with($key, "-$month")) {
                    $value = $val;
                    break;
                }
            }
            $data[] = $value;
        }

        $settings2 = [
            'chart_title'           => 'Bookings for Month',
            // 'chart_type'            => 'latest_entries',
            // 'report_type'           => 'group_by_date',
            // 'model'                 => 'App\Models\Booking',
            // 'group_by_field'        => 'created_at',
            // 'group_by_period'       => 'day',
            // 'aggregate_function'    => 'count',
            // 'filter_field'          => 'created_at',
            // 'filter_days'           => '30',
            // 'group_by_field_format' => 'Y-m-d H:i:s',
            // 'column_class'          => 'col-md-6',
            // 'entries_number'        => '10',
            'fields'                => [

                'service'        => 'name',
                'season'         => 'name',
                'status_booking' => 'name',
                'lead_name'      => '',
                'created_at'         => '',
            ],
            //'translation_key' => 'booking',
            'data'           => \App\Models\Booking::with('service:id,name', 'season:id,name', 'status_booking:id,name')->latest()->take(10)->get(),
        ];

        $settings3 = [
            'chart_title'           => 'Notifications',
            // 'chart_type'            => 'latest_entries',
            // 'report_type'           => 'group_by_date',
            // 'model'                 => 'App\Models\UserAlert',
            // 'group_by_field'        => 'created_at',
            // 'group_by_period'       => 'day',
            // 'aggregate_function'    => 'count',
            // 'filter_field'          => 'created_at',
            // 'filter_days'           => '30',
            // 'group_by_field_format' => 'Y-m-d H:i:s',
            // 'column_class'          => 'col-md-4',
            // 'entries_number'        => '5',
            'fields'                => [
                'alert_text' => '',
                'user'       => 'name',
                'created_at' => '',
            ],
            //'translation_key' => 'userAlert',
            'data'           => \App\Models\UserAlert::with('users:id,name')->latest()->take(10)->get(),
        ];


        $settings4 = [
            'chart_title'           => 'Booking Status',
            // 'chart_type'            => 'latest_entries',
            // 'report_type'           => 'group_by_date',
            // 'model'                 => 'App\Models\Booking',
            // 'group_by_field'        => 'created_at',
            // 'group_by_period'       => 'day',
            // 'aggregate_function'    => 'count',
            // 'filter_field'          => 'created_at',
            // 'filter_days'           => '30',
            // 'group_by_field_format' => 'Y-m-d H:i:s',
            // 'column_class'          => 'col-md-6',
            // 'entries_number'        => '10',
            'fields'                => [
                'season'         => 'name',
                'status_booking' => 'name',
                'lead_name'      => '',
                'due_date'       => '',
                'tot_paid'       => '',
            ],
            'translation_key' => 'booking',
            'data'           => \App\Models\Booking::with('season:id,name', 'status_booking:id,name')->latest()->take(10)->get(),
        ];


        $settings5 = [
            'chart_title'           => 'Customer Manifest',
            // 'chart_type'            => 'latest_entries',
            // 'report_type'           => 'group_by_date',
            // 'model'                 => 'App\Models\CustomerManifest',
            // 'group_by_field'        => 'created_at',
            // 'group_by_period'       => 'day',
            // 'aggregate_function'    => 'count',
            // 'filter_field'          => 'created_at',
            // 'filter_days'           => '30',
            // 'group_by_field_format' => 'Y-m-d H:i:s',
            // 'column_class'          => 'col-md-6',
            // 'entries_number'        => '10',
            'fields'                => [
                'is_indemnity' => '',
                'customer'     => 'full_name',
                'booking'      => 'reference',
                'created_at'   => '',
            ],
            'translation_key' => 'customerManifest',
            'data'           => \App\Models\CustomerManifest::with('customer:id,full_name', 'booking:id,reference')->latest()->take(10)->get(),
        ];

        return Inertia::render('Dashboard', [
            'chart1' => [
                'title' => $chart1->options['chart_title'],
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => $chart1->getDatasets()[0]['name'] ?? 'Data',
                        'data' => $data,
                        'backgroundColor' => 'rgba(75,192,192,0.2)',
                        'borderColor' => 'rgba(75,192,192,1)',
                        'fill' => true,
                    ]
                ],
                'type' => $chart1->options['chart_type'],
                'options' => [
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'ticks' => [
                                'stepSize' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'latestBookings' => $settings2,
            'notifications' => $settings3,
            'statusBookings' => $settings4,
            'customerManifest' => $settings5,
        ]);

        //return view('home', compact('chart1', 'settings2', 'settings3', 'settings4', 'settings5'));
    }


    // public function index()
    // {
    //     return Inertia::render('Dashboard');
    //     // return view('home');
    // }



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
