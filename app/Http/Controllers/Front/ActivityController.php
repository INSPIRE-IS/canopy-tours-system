<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInventory;
use App\Models\BookingPayment;
use App\Models\Closeout;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryTimeSlot;
use App\Models\InventoryTypeDay;
use App\Models\Season;
use App\Models\Service;
use App\Models\Team;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PHPUnit\Framework\Constraint\IsFalse;
use Route;
use Str;

class ActivityController extends Controller
{

    public function index($team_id = 0)
    {
        $teamId = $team_id;
        //dd($request);

        $teams = Team::select('id', 'name')->get();
        $services = Service::with([

            'file',
            'servicefieldValues.field',
            'seasons.inventory',
        ]);

        if ($teamId) {
            $services->where('team_id', $teamId);
        }

        $services = $services->get();
        // $services->each(function ($service) {
        //     $service->setRelation('seasons', $service->seasons->filter(function ($season) {
        //         return $season->inventory !== null;
        //     })->values()); // reindex keys
        // });


        //         $formatted = $activities->map(function ($service) {
        //     return [
        //         'id' => $service->id,
        //         'category' => $service->category ?? 'Canopy Tour', // if category exists
        //         'title' => $service->title,
        //         'price' => $service->price,
        //         'image' => $service->image,
        //         'badge' => $service->badge ?? '1 Adult', // if applicable
        //         'gift' => $service->is_giftable ?? true,
        //         'minPeople' => $service->min_people ?? 1,
        //         'seasons' => $service->seasons->map(function ($season) {
        //             return [
        //                 'id' => $season->id,
        //                 'name' => $season->name,
        //                 'inventory' => $season->inventory, // already formatted if needed
        //             ];
        //         }),
        //     ];
        // });
        $activities = $services->flatMap(function ($service) {
            //Log::info('season ' . $service->name);
            return $service->seasons->map(function ($season) use ($service) {
                //Log::info('season ' . $season->name);
                $inventory = $season->inventory;
                // if (!$inventory) {
                //     //Log::info('inventory none ');
                //     return null; // skip incomplete seasons
                // }
                //Log::info('inventory ' . $inventory->season_id);
                //dd($inventory);
                $fieldValues = $service->serviceFieldValues->mapWithKeys(function ($fv) {
                    return [$fv->field->name => $fv->value];
                });
                //dd($fieldValues);
                return [
                    'id'         => optional($inventory)->id,
                    'service_id'      => $service->id,
                    'service'      => $service->name,
                    'season_id'      => $season->id,
                    'image'      => $service->file ? '/storage/' . $service->file->path : null,
                    'badge'      => $fieldValues['badge'] ?? null,
                    'gift'       => filter_var($fieldValues['gift'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'minPeople'  => (int) ($fieldValues['min_people'] ?? 1),
                    'price'      => optional($season)->rake_rate_adult ?? null,
                    'start_date' => optional($season)->start_date ?? null,
                    'end_date'   => optional($season)->end_date ?? null,
                    'capacity'   => optional($season)->capacity ?? null,
                ];
            })->filter(); // remove nulls
        });
        //dd($activities);

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'activities' => $activities,
            'teams' => $teams,
            'team_id' => $teamId ?? 0,
        ]);
    }


    public function activity(Request $request)
    {
        // dd($request->all());
        $activity_id = $request->activity_id;
        $startDate = Carbon::parse($request->input('start_date', now()));

        $viewMode = $request->input('view_mode', 'week');
        $season = Season::with([
            'service',
            'service.file',
            'service.serviceFieldValues.field',
            'inventory'
        ])->findOrFail($activity_id);
        // dd($season->service);
        $inventory = $season->inventory;

        if (!$inventory) {
            abort(404, 'This season has no inventory.');
        }

        $fieldValues = $season->service->serviceFieldValues->mapWithKeys(function ($fv) {
            return [$fv->field->name => $fv->value];
        });
        //dd($fieldValues);
        $activity = [
            'id'         => $inventory->id,
            'service'    => $season->service?->name,
            'season'     => $season->name,
            'image'      => $season->file ? '/storage/' . $season->file->path : null,
            'badge'      => $fieldValues['badge'] ?? null,
            'gift'       => filter_var($fieldValues['gift'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'minPeople'  => (int) ($fieldValues['min_people'] ?? 1),
            'price'      => $inventory->rake_rate_adult ?? null,
            'start_date' => $inventory->start_date,
            'end_date'   => $inventory->end_date,
            'capacity'   => $inventory->capacity,
            'description' => $fieldValues['Description'] ?? null,
            'condition_text' => $fieldValues['Condition Text'] ?? null,
            'activity' => $fieldValues['Activity Description'] ?? null,
            'arrangements' => $fieldValues['Arrangements'] ?? null,
            'requirements' => $fieldValues['Requirements'] ?? null,
            'inclusions' => $fieldValues['Inclusions'] ?? null,
            'exclusions' => $fieldValues['Exclusions'] ?? null,
            'notes' => $fieldValues['Notes'] ?? null,
        ];
        $schedule = [];
        // dd($activity);
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 5,
            'month' => 30,
            default => 5
        };
        $endDate = (clone $startDate)->addDays($days - 1);
        return Inertia::render('Activities', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'serviceId' => $season->service_id,
            'seasonId' => $season->id,
            'activityId' => $activity_id,
            'activities' => $activity,
            'schedule' => $schedule,
            'start_date' => $startDate->toDateString(),
            'view_mode' => $viewMode,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn($d) => $d->format('D d'))
                ->values(),
            'availableTimes' => [],
        ]);
    }

    public function calendar(Request $request)
    {

        $activityId = $request->input('activity_id');
        $viewMode = $request->input('view_mode', 'week');
        $startDate = Carbon::parse($request->input('start_date', now()));
        $totalRequested = (int) $request->input('adults', 0) + (int) $request->input('children', 0);
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7
        };
        $endDate = (clone $startDate)->addDays($days - 1);
        //dd($endDate);
        $season = Season::findOrFail($activityId);

        $inventory = Inventory::with(['inventory_type_days', 'inventory_time_slots'])->where('season_id', $season->id)->first();
        //dd($inventory);
        if ($startDate->lt(Carbon::parse($season->start_date)) || $endDate->gt(Carbon::parse($season->end_date))) {
            return response()->json([
                'schedule' => [],
                'days' => [],
                'message' => 'Selected date is outside the inventory availability period.',
            ], 200); // Or 200 with flag
        }

        if (true) {
            $bookingData = DB::table('bookings as b')
                ->leftJoin('booking_inventories as bi', 'bi.booking_id', '=', 'b.id')
                ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
                ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
                ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
                ->leftJoin('inventory_overrides as io', 'io.id', '=', 'bi.inventory_override_id')
                ->where('b.inventory_id', $activityId)
                ->whereBetween('bi.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->select([
                    'bi.date',
                    'td.name as day',
                    'its.start_time as time',
                    DB::raw('SUM(bi.pax) as total')
                ])
                ->groupBy('bi.date', 'td.name', 'its.start_time')
                ->get()
                ->keyBy(fn($row) => $row->date . '_' . $row->time);
        }
        // dd($bookingData);

        //dd($inventory);
        $allowedDays = $inventory->inventory_type_days->pluck('type_day_id')->toArray();

        $closeouts = Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->get();
        //dd($closeouts);
        $blockedDates = $closeouts->flatMap(function ($c) {
            logger("Closeout: {$c->start_date} to {$c->end_date}");
            // $start = Carbon::parse($c->start_date);
            // $end = Carbon::parse($c->end_date);

            // return CarbonPeriod::create($start, $end)
            //     ->map(fn($d) => $d->format('Y-m-d'));

            $start = Carbon::parse($c->start_date);
            $end = Carbon::parse($c->end_date);

            $dates = CarbonPeriod::create($start, $end)->toArray();
            logger("Generated dates: ", $dates);

            return collect($dates)->map(fn($d) => $d->format('Y-m-d'));
        })->unique()->values()->toArray();
        //dd($blockedDates);
        $timeSlots = $inventory->inventory_time_slots->pluck('start_time')->unique()->sort();
        $schedule = [];

        foreach ($timeSlots as $time) {
            $row = [
                'time' => $time,
                'days' => []
            ];

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $label = $date->format('D d');
                $dayOfWeek = $date->dayOfWeekIso;
                $dateStr = $date->toDateString();

                if (!in_array($dayOfWeek, $allowedDays)) {
                    $row['days'][$label] = [
                        'date' => $date->format('Y-m-d'),
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Not an allowed day',
                    ];
                    continue;
                }

                if (in_array($dateStr, $blockedDates)) {
                    $row['days'][$label] = [
                        'date' => $date->format('Y-m-d'),
                        'slots' => 0,
                        'status' => 'blocked',
                        'note' => 'Blocked due to closeout',
                    ];
                    continue;
                }

                // once we have the booking data, we can calculate the remaining slots
                if (true) {
                    $dateTimeStr = $dateStr . '_' . $time;
                    $booked = $bookingData[$dateTimeStr]->total ?? 0;
                    $remaining = $season->capacity - $booked;
                    if ($remaining < $totalRequested) {
                        $row['days'][$label] = [
                            'date' => $date->format('Y-m-d'),
                            'slots' => $remaining,
                            'status' => 'full',
                            'note' => 'Fully booked',
                        ];
                    } else {
                        $row['days'][$label] = [
                            'date' => $date->format('Y-m-d'),
                            'slots' => $remaining,
                            'status' => 'available',
                            'rake_rate_adult' => $season->rake_rate_adult,
                        ];
                    }
                }
                // if we don't have the booking data, we can't calculate the remaining slots
                if (false) {
                    $row['days'][$label] = [
                        'date' => $date->format('Y-m-d'),
                        'slots' => $season->capacity,
                        'status' => 'available',
                        'rake_rate_adult' => $season->rake_rate_adult,
                    ];
                }
            }

            $schedule[] = $row;
        }

        return response()->json([
            'schedule' => $schedule,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn($d) => $d->format('D d'))
                ->values(),
            'message' => '',
        ]);
    }

    public function activity1(Request $request)
    {
        // dd($request->all());
        $activity_id = $request->activity_id;
        $viewMode = $request->input('view_mode', 'week'); // 'day', 'week', 'month'
        $startDate = Carbon::parse($request->input('start_date', now()));

        $season = Season::with([
            'service',
            'file',
            'seasonFieldValues.field',
            'inventory'
        ])->findOrFail($activity_id);

        $inventory = $season->inventory;

        if (!$inventory) {
            abort(404, 'This season has no inventory.');
        }

        $fieldValues = $season->seasonFieldValues->mapWithKeys(function ($fv) {
            return [$fv->field->name => $fv->value];
        });
        //dd($fieldValues);
        $activity = [
            'id'         => $inventory->id,
            'service'    => $season->service?->name,
            'season'     => $season->name,
            'image'      => $season->file ? '/storage/' . $season->file->path : null,
            'badge'      => $fieldValues['badge'] ?? null,
            'gift'       => filter_var($fieldValues['gift'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'minPeople'  => (int) ($fieldValues['min_people'] ?? 1),
            'price'      => $inventory->rake_rate_adult ?? null,
            'start_date' => $inventory->start_date,
            'end_date'   => $inventory->end_date,
            'capacity'   => $inventory->capacity,
            'description' => $fieldValues['Description'] ?? null,
            'condition_text' => $fieldValues['Condition Text'] ?? null,
            'activity' => $fieldValues['Activity Description'] ?? null,
            'arrangements' => $fieldValues['Arrangements'] ?? null,
            'requirements' => $fieldValues['Requirements'] ?? null,
            'inclusions' => $fieldValues['Inclusions'] ?? null,
            'exclusions' => $fieldValues['Exclusions'] ?? null,
            'notes' => $fieldValues['Notes'] ?? null,
        ];

        // dd($activity);
        // Step 1: Load inventory with relationships
        $inventory = Inventory::with(['timeSlots', 'typeDays'])->findOrFail($activity_id);

        $allowedDays = $inventory->typeDays->pluck('type_day_id')->toArray();

        // Step 2: Determine date range based on view mode
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 5,
            'month' => 30,
            default => 5
        };

        $endDate = (clone $startDate)->addDays($days - 1);
        //dd($inventory);
        // Step 3: Get blocked (closeout) dates
        $closeouts = Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->get();
        //dd($closeouts);
        $blockedDates = $closeouts->flatMap(function ($c) {
            // logger("Closeout: {$c->start_date} to {$c->end_date}");
            $start = Carbon::parse($c->start_date);
            $end = Carbon::parse($c->end_date);

            $dates = CarbonPeriod::create($start, $end)->toArray();
            // logger("Generated dates: ", $dates);

            return collect($dates)->map(fn($d) => $d->format('Y-m-d'));
        })->unique()->values()->toArray();
        //dd($blockedDates);
        // Pull bookings for this period
        if (false) {
            $bookingData = DB::table('bookings')
                ->where('inventory_id', $activity_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->select('date', 'time', DB::raw('SUM(quantity) as total'))
                ->groupBy('date', 'time')
                ->get()
                ->keyBy(fn($row) => $row->date . '_' . $row->time);
        }
        // (Optional) staff schedule unavailability maybe from guide set to 0
        if (false) {
            $staffUnavailable = DB::table('staff_schedules')
                ->where('service_id', $inventory->service_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('status', 'unavailable')
                ->get()
                ->map(fn($row) => $row->date . '_' . $row->time)
                ->toArray();
        }


        // Step 4: Build schedule
        $timeSlots = $inventory->timeSlots->pluck('start_time')->unique()->sort();

        $schedule = [];

        foreach ($timeSlots as $time) {
            $row = [
                'time' => $time,
                'days' => []
            ];

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $label = $date->format('D d'); // e.g., Mon 24
                $dayOfWeek = $date->dayOfWeekIso; // 1=Mon, 7=Sun
                $dateStr = $date->toDateString();
                $key = $dateStr . '_' . $time;

                // Skip if not allowed day or closed out
                if (!in_array($dayOfWeek, $allowedDays)) {
                    $row['days'][$label] = [
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Not an allowed day',
                    ];
                    continue;
                }

                if (in_array($date->toDateString(), $blockedDates)) {
                    $row['days'][$label] = [
                        'slots' => 0,
                        'status' => 'blocked',
                        'note' => 'Blocked due to closeout',
                    ];
                    continue;
                }
                // possibly need this if we allow for staff on guide set to 0
                if (false) {
                    // Skip if staff is unavailable
                    if (in_array($key, $staffUnavailable)) {
                        $row['days'][$label] = [
                            'slots' => 0,
                            'status' => 'unavailable',
                            'note' => 'Staff unavailable',
                        ];
                        continue;
                    }
                }


                // once we have the booking data, we can calculate the remaining slots
                if (false) {
                    $booked = $bookingData[$key]->total ?? 0;
                    $remaining = $inventory->capacity - $booked;
                    if ($remaining <= 0) {
                        $row['days'][$label] = [
                            'slots' => 0,
                            'status' => 'full',
                            'note' => 'Fully booked',
                        ];
                    } else {
                        $row['days'][$label] = [
                            'slots' => $remaining,
                            'status' => 'available',
                            'price' => $inventory->rake_rate_adult,
                        ];
                    }
                }
                // if we don't have the booking data, we can't calculate the remaining slots
                if (true) {
                    $row['days'][$label] = [
                        'slots' => $inventory->capacity,
                        'status' => 'available',
                        'price' => $inventory->rake_rate_adult,
                    ];
                }
            }

            $schedule[] = $row;
        }




        return Inertia::render('Activities', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'activities' => $activity,
            'activityId' => $activity_id,
            'schedule' => $schedule,
            'start_date' => $startDate->toDateString(),
            'view_mode' => $viewMode,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn($d) => $d->format('D d'))
                ->values(),
            'availableTimes' => [], //$availableTimes,
        ]);
    }

    public function store(Request $request)
    {
        // TODO: validate the inventory is still available

        $validated = $request->validate([
            'name' => 'required|string',
            'surname' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'activity_id' => 'required|integer',
        ]);
        try {
            $booking = DB::transaction(function () use ($validated) {
                $activity_id = $validated['activity_id'];

                $season = Season::findOrFail($activity_id);
                $inventory = Inventory::where('season_id', $season->id)->first();
                //$FullNameOfDay = Carbon::parse($validated['date'])->format('l'); // e.g., Monday
                //$ShortNameOfDay = Carbon::parse($validated['date'])->format('D'); // e.g., Mon
                $isoNameOfDay = Carbon::parse($validated['date'])->format('N'); // iso week starts on Monday
                //$numericNameOfDay = Carbon::parse($validated['date'])->format('w'); // week starts on Sunday


                if (!$inventory) {
                    abort(404, 'This season has no inventory.');
                }
                $inventoryTimeSlot = InventoryTimeSlot::where('inventory_id', $inventory->id)->where('start_time', $validated['time'])->first();
                //$inventoryTypeDay = InventoryTypeDay::where('inventory_id', $inventory->id)->first();

                if (!$inventoryTimeSlot) {
                    abort(404, 'This inventory has no time slot.');
                }

                // 1. Create or find customer
                $customer = Customer::firstOrCreate([
                    'email_address' => $validated['email'],
                ], [
                    'full_name' => $validated['name'] . ' ' . $validated['surname'],
                    'contact_number' => $validated['phone'],
                ]);
                $adultsCount = (int) ($validated['adults'] ?? 0);
                $amount_adults = $inventory->rake_rate_adult * $adultsCount;
                $childrenCount = (int) ($validated['children'] ?? 0);
                $amount_children = $inventory->rake_rate_children * $childrenCount;
                //$guidesCount = (int) ($validated['guides'] ?? 0);
                $amount_guides = $inventory->rake_rate_guide * 1;
                $amount = $amount_adults + $amount_children + $amount_guides;

                // 2. Create booking
                $booking = Booking::create([
                    'lead_name'      => $validated['name'] . ' ' . $validated['surname'],
                    'status_booking_id' => 1,
                    'is_sms'         => 0,
                    'is_corporate'   => 0,
                    'due_date'       => '2025-07-01',
                    'tot_paid'       => 0,
                    'grand_tot_due'  => $amount,
                    'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                    'service_id'     => $season->service_id,
                    'season_id'      => $season->id,
                    'inventory_id'   => $inventory->id,
                    'customers'      => [$customer->id],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'deleted_at'     => null,
                    'team_id'        => null,
                ]);

                // Prepare pivot data
                $pivotData = [];


                $pivotData[$customer->id] = [
                    'team_id' => null,   // TODO: get team from the page or session
                    'is_lead' => 1,     // First customer is lead, rest are not
                ];


                // Sync with pivot data
                $booking->customers()->sync($pivotData);

                // Optional Enhancements
                // You can also lock inventory slots with for update queries if you expect concurrency.
                // Use Laravel's retry helper if you're worried about deadlocks (rare unless high traffic).

                //TODO: Add booking details
                $bookingDetail = BookingDetail::create([
                    'booking_id'     => $booking->id,
                    'is_wholesaler_booking' => 0,
                    'is_needs_pickup' => 0,
                    'is_web_booking' => 0,
                    'is_videos_photos' => 0,
                    'is_exclusive_resource_use' => 0,
                    'is_override_time_slots' => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'team_id'        => null,
                ]);
                //TODO: Add booking inventory
                $bookingInventory = BookingInventory::create([
                    'booking_id'     => $booking->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'date'           => $validated['date'],
                    'tot_adults'     => $adultsCount,
                    'tot_children'   => $childrenCount,
                    'tot_guides'     => 1,
                    'pax'            => $adultsCount + $childrenCount,
                    'inventory_type_day_id' => $isoNameOfDay,
                    'inventory_time_slot_id' => $inventoryTimeSlot->id,
                    'inventory_override_id' => null,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'team_id'        => null,
                ]);
                //TODO: Add booking fees
                $bookingFee = BookingFee::create([
                    'booking_id'     => $booking->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'tot_adults'     => $amount_adults,
                    'tot_children'   => $amount_children,
                    'tot_guides'     => $amount_guides,
                    'is_voucher'     => 0,
                    'voucher_code'   => '',
                    'invoice_note'   => '',
                    'is_email_reminder' => 0,
                    'booking_note'   => '',
                    'tot_amount'     => $amount,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'team_id'        => null,
                ]);

                return $booking;
            });

            // 4. Save to booking_inventories or other linked tables
            return response()->json([
                'success' => true,
                'booking_id' => $booking->id,
                'redirect_url' => route('front.payment.review', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
            ]);
            //return redirect()->back()->with('booking_id', $booking->id);
        } catch (\Throwable $e) {
            // Log the full error for debugging
            Log::error('Booking failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ], 500);
        }
    }


    public function voucher(Request $request)
    {
        dd($request->all());
        $activityId = $request->activity_id;
        // return view('front.voucher', compact('activityId'));
        return Inertia::render('GiftVoucher', ['activityId' => $activityId]);
    }


    public function showTestOrder()
    {
        // Sample data for testing
        $order_no = 'ORD12345';
        $po_no = 'PO67890';
        $customer_code = 'CUST001';

        $customer = (object) [
            'Name' => 'ACME Corp',
            'SoldToAddr2' => '123 SoldTo Street',
            'SoldToAddr3' => 'SoldTo Suburb',
            'SoldToAddr4' => 'SoldTo City',
            'SoldToAddr5' => 'SoldTo Country',
            'ShipToAddr2' => '456 ShipTo Road',
            'ShipToAddr3' => 'ShipTo Area',
            'ShipToAddr4' => 'ShipTo Town',
            'ShipToAddr5' => 'ShipTo Province',
            'CompanyTaxNumber' => 'VAT123456789',
        ];

        $orderItems = [
            [
                'col2' => 'ITEM001',
                'col3' => 'Widget A',
                'col6' => 2,
                'col5' => 'R 100.00',     // original price
                'col23' => 'R 90.00',     // discounted price
                'col8' => 'Blue Variant',
            ],
            [
                'col2' => 'ITEM002',
                'col3' => 'Widget B',
                'col6' => 1,
                'col5' => 'R 200.00',
                'col23' => '',            // no discount
                'col8' => 'Red Variant',
            ],
        ];

        // Calculate total price (excluding VAT)
        $totalPrice = 0;
        foreach ($orderItems as $item) {
            $price = $item['col23'] != "" ? str_replace("R ", "", $item['col23']) : str_replace("R ", "", $item['col5']);
            $totalPrice += (float) $price * $item['col6'];
        }
        $totalPriceFormatted = "R " . number_format($totalPrice, 2, ".", "");

        return view('order', compact('order_no', 'po_no', 'customer_code', 'customer', 'orderItems', 'totalPrice'));
    }
}
