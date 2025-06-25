<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Closeout;
use App\Models\Inventory;
use App\Models\Season;
use App\Models\Service;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PHPUnit\Framework\Constraint\IsFalse;
use Route;


class ActivityController extends Controller
{

    public function index(Request $request)
    {
        $services = Service::with([

            'seasons.file',
            'seasons.seasonfieldValues.field',
            'seasons.inventory',
        ])->get();
        //dd($services);
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
                $fieldValues = $season->seasonFieldValues->mapWithKeys(function ($fv) {
                    return [$fv->field->name => $fv->value];
                });

                return [
                    'id'         => optional($inventory)->id,
                    'service'      => $service->name,
                    'season'   => $season->name,
                    'image'      => $season->file ? '/storage/' . $season->file->path : null,
                    'badge'      => $fieldValues['badge'] ?? null,
                    'gift'       => filter_var($fieldValues['gift'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'minPeople'  => (int) ($fieldValues['min_people'] ?? 1),
                    'price'      => optional($inventory)->rake_rate_adult ?? null,
                    'start_date' => optional($inventory)->start_date ?? null,
                    'end_date'   => optional($inventory)->end_date ?? null,
                    'capacity'   => optional($inventory)->capacity ?? null,
                ];
            })->filter(); // remove nulls
        });

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'activities' => $activities,
        ]);
    }


    public function activity(Request $request)
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


    public function voucher(Request $request)
    {
        dd($request->all());
        $activityId = $request->activity_id;
        // return view('front.voucher', compact('activityId'));
        return Inertia::render('GiftVoucher', ['activityId' => $activityId]);
    }
}
