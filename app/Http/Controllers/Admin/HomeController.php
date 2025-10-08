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
        //dd($settings1);
        $bookings = \App\Models\Booking::with([
            'booking_items.service:id,name',
            'booking_items.season:id,name',
            'type_booking:id,name'
        ])
            ->latest()
            ->take(10)
            ->get();

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
                'type_booking' => 'name',
                'lead_name'      => '',
                'created_at'         => '',
            ],
            //'translation_key' => 'booking',
            'data' => $bookings->flatMap(function ($booking) {
                return $booking->booking_items->map(function ($item) use ($booking) {
                    return [
                        'service'      => $item->service ?? null,
                        'season'       => $item->season ?? null,
                        'type_booking' => $booking->type_booking ?? null,
                        'lead_name'    => $booking->lead_name ?? null,
                        'created_at'   => $booking->created_at->format('Y-m-d'),
                    ];
                });
            }),
        ];
        // dd($settings2);
        $settings3 = [
            'chart_title'           => 'Notifications',
            // 'chart_type'            => 'latest_entries',
            // 'report_type'           => 'group_by_date',
            // 'model'                 => 'App\Models\Alert',
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
            //'translation_key' => 'Alert',
            'data'           => \App\Models\Alert::with('users:id,name')->latest()->take(10)->get(),
        ];
        // dd($settings3);
        $booking = \App\Models\Booking::with('booking_items', 'booking_items.season:id,name', 'status_payment:id,name')->latest()->take(10)->get();
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
                'status_payment' => 'name',
                'lead_name'      => '',
                'due_date'       => '',
                'tot_paid'       => '',
            ],
            'translation_key' => 'booking',
            'data' => $bookings->flatMap(function ($booking) {
                return $booking->booking_items->map(function ($item) use ($booking) {
                    return [
                        'season'       => $item->season ?? null,
                        'status_payment' => $booking->status_payment ?? null,
                        'lead_name'    => $booking->lead_name ?? null,
                        'due_date'     => $booking->due_date ?? null,
                        'tot_paid'     => $booking->tot_paid ?? null,
                    ];
                });
            }),
        ];
        // dd($settings4);
        $manifests = \App\Models\CustomerManifest::with([
            'customer:id,full_name',
            'booking_item.booking:id,reference' // 👈 note singular now
        ])
            ->latest()
            ->take(10)
            ->get();
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
            'data' => $manifests->map(function ($manifest) {
                return [
                    'is_indemnity' => $manifest->is_indemnity ? 'Yes' : 'No',
                    'customer'     => $manifest->customer ?? null,
                    'booking'      => $manifest->booking_item?->booking,
                    'created_at'   => $manifest->created_at->format('Y-m-d'),
                ];
            }),
        ];

        //dd($settings5);

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
