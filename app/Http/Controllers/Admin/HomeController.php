<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingDiscountItem;
use App\Models\BookingItem;
use App\Models\Discount;
use App\Models\Promotion;
use Illuminate\Http\Request;
use LaravelDaily\LaravelCharts\Classes\LaravelChart;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $teamId = Session::get('team_id') ?? $user?->team_id;

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
            // For super admins, respect the selected team when set.
            'where_raw'             => $teamId ? ('team_id = ' . (int) $teamId) : '',
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
            'booking_items.booking_inventory:id,booking_item_id,date,inventory_time_slot_id',
            'booking_items.booking_inventory.inventory_time_slot:id,start_time',
            'type_booking:id,name'
        ])
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
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
                //'season'         => 'name',
                //'type_booking' => 'name',
                'lead_name'      => '',
                'activity_date'  => '',
                'activity_time'  => '',
                'created_at'         => '',
            ],
            //'translation_key' => 'booking',
            'data' => $bookings->flatMap(function ($booking) {
                return $booking->booking_items->map(function ($item) use ($booking) {
                    return [
                        'service'      => $item->service ?? null,
                        //'season'       => $item->season ?? null,
                        //'type_booking' => $booking->type_booking ?? null,
                        'lead_name'    => $booking->lead_name ?? null,
                        'activity_date' => $item->booking_inventory?->date,
                        'activity_time' => $item->booking_inventory?->inventory_time_slot?->start_time,
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
                'assigned_to' => 'name',
                'created_at' => '',
            ],
            //'translation_key' => 'Alert',
            'data'           => \App\Models\Alert::with('users:id,name')
                ->when($teamId, fn($query) => $query->where('team_id', $teamId))
                ->whereHas('users', fn($query) => $query
                    ->where('users.id', $user?->id)
                    ->where('alert_user.complete', false))
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($alert) {
                    return [
                        'id' => $alert->id,
                        'alert_text' => $alert->alert_text,
                        'alert_link' => $alert->alert_link,
                        'url' => $alert->alert_link ?: route('admin.bookings.index', [], false),
                        'context_url' => route('admin.user-alerts.context', $alert->id, false),
                        'resolve_url' => route('admin.user-alerts.resolve', $alert->id, false),
                        'has_context' => (bool) ($alert->booking_id && $alert->booking_item_id && $alert->customer_id),
                        'assigned_to' => $alert->users->first(),
                        'created_at' => $alert->created_at?->format('Y-m-d H:i:s'),
                    ];
                }),
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
            'booking_item.booking:id,reference' // note singular now
        ])
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
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
        $promotions = Promotion::with('service:id,name')
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
            ->latest()
            ->take(10)
            ->get();

        $promotionUsage = BookingItem::query()
            ->selectRaw('promotion_id, COUNT(*) as usage_count')
            ->whereNotNull('promotion_id')
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
            ->groupBy('promotion_id')
            ->pluck('usage_count', 'promotion_id');

        $settings6 = [
            'chart_title' => 'Latest Promotions',
            'fields' => [
                'name' => '',
                'promo_code' => '',
                'status' => '',
                'usage_count' => '',
                'service' => 'name',
            ],
            'data' => $promotions->map(function ($promotion) use ($promotionUsage) {
                return [
                    'name' => $promotion->name,
                    'promo_code' => $promotion->promo_code,
                    'status' => $promotion->status === 'active' ? 'Active' : 'Inactive',
                    'usage_count' => (int) ($promotionUsage[$promotion->id] ?? 0),
                    'service' => $promotion->service,
                ];
            }),
        ];

        $discounts = Discount::with('services:id,name')
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
            ->latest()
            ->take(10)
            ->get();

        $discountUsage = BookingDiscountItem::query()
            ->selectRaw('discount_id, COUNT(*) as usage_count')
            ->whereNotNull('discount_id')
            ->whereHas('booking_discount.booking', function ($query) use ($teamId) {
                $query->when($teamId, fn($sub) => $sub->where('team_id', $teamId));
            })
            ->groupBy('discount_id')
            ->pluck('usage_count', 'discount_id');

        $legacyDiscountUsage = BookingItem::query()
            ->selectRaw('discount_id, COUNT(*) as usage_count')
            ->whereNotNull('discount_id')
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
            ->groupBy('discount_id')
            ->pluck('usage_count', 'discount_id');

        $settings7 = [
            'chart_title' => 'Latest Discounts',
            'fields' => [
                'name' => '',
                'applies_to' => '',
                'status' => '',
                'usage_count' => '',
                'services' => '',
            ],
            'data' => $discounts->map(function ($discount) use ($discountUsage, $legacyDiscountUsage) {
                $usageCount = (int) ($discountUsage[$discount->id] ?? 0)
                    + (int) ($legacyDiscountUsage[$discount->id] ?? 0);

                return [
                    'name' => $discount->name,
                    'applies_to' => str_replace('_', ' ', ucfirst($discount->applies_to)),
                    'status' => 'Active',
                    'usage_count' => $usageCount,
                    'services' => $discount->services->pluck('name')->take(2)->implode(', ')
                        . ($discount->services->count() > 2 ? ' +' . ($discount->services->count() - 2) : ''),
                ];
            }),
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
            'promotionsSummary' => $settings6,
            'discountsSummary' => $settings7,
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
