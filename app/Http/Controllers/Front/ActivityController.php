<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingPriceLine;
use App\Models\Closeout;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\GiftVoucher;
use App\Models\GiftVoucherItem;
use App\Models\Inventory;
use App\Models\InventoryTimeSlot;
use App\Models\InventoryTypeDay;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\TypeInvoice;
use App\Models\Promotion;
use App\Models\Season;
use App\Models\Service;
use App\Models\Team;
use App\Services\Calendar\ServiceCalendarBuilder;
use App\Services\PricingService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PHPUnit\Framework\Constraint\IsFalse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Route;
use Spatie\Browsershot\Browsershot;
use Str;

class ActivityController extends Controller
{
    private function resolveInventoryTypeDayId(Inventory $inventory, string $date): int
    {
        $typeDayId = Carbon::parse($date)->dayOfWeekIso;
        $inventoryTypeDayId = InventoryTypeDay::where('inventory_id', $inventory->id)
            ->where('type_day_id', $typeDayId)
            ->value('id');

        if (!$inventoryTypeDayId) {
            abort(404, 'Selected booking date is not available for this inventory.');
        }

        return (int) $inventoryTypeDayId;
    }

    private function syncBookingPriceLines(BookingItem $bookingItem, array $pricing, string $activityDate, ?string $activityTime): void
    {
        $bookingItem->price_lines()->delete();

        foreach (($pricing['line_items'] ?? []) as $line) {
            $count = (int) ($line['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }

            $baseRate = round((float) ($line['base_rate'] ?? 0), 2);
            $finalRate = round((float) ($line['final_rate'] ?? $baseRate), 2);
            $label = $line['adjustment_label'] ?? null;
            $sourceType = 'rack';
            $sourceId = null;

            if (!empty($bookingItem->promotion_id) && $label) {
                $sourceType = 'promotion';
                $sourceId = $bookingItem->promotion_id;
            }

            BookingPriceLine::create([
                'booking_id' => $bookingItem->booking_id,
                'booking_item_id' => $bookingItem->id,
                'team_id' => $bookingItem->team_id,
                'activity_date' => $activityDate,
                'activity_time' => $activityTime,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'pax_type' => $line['type'] ?? 'adult',
                'count' => $count,
                'base_rate' => $baseRate,
                'final_rate' => $finalRate,
                'line_total' => round((float) ($line['line_total'] ?? ($finalRate * $count)), 2),
                'label' => $label,
                'meta' => [],
            ]);
        }
    }

    public function index($team_id = 0)
    {

        $teamId = $team_id;
        //dd($request);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $canViewUnpublished = $user && $user->can('teams_publish');
        $canViewInternalSeasons = (bool) $user;
        $canViewStaffOnlyServices = (bool) $user;

        $teamsQuery = Team::select('id', 'name', 'is_published');
        if (!$canViewUnpublished) {
            $teamsQuery->where('is_published', true);
        }
        $teams = $teamsQuery->orderBy('name')->get();

        if ($teamId) {
            $requestedTeam = Team::select('id', 'is_published')->findOrFail($teamId);
            if (!$requestedTeam->is_published && !$canViewUnpublished) {
                abort(404);
            }
        }

        $services = Service::with([
            'file',
            'servicefieldValues.field',
            'seasons.inventory',
            'activePromotions',
        ]);

        if ($teamId) {
            $services->where('team_id', $teamId);
        } elseif (!$canViewUnpublished) {
            // Only show services for published teams on the public frontend.
            $services->whereIn('team_id', $teams->pluck('id')->all());
        }
        if (!$canViewStaffOnlyServices) {
            $services->where('is_online_bookable', true);
        }

        $services = $services->get();
        //dd($services[0]->activePromotions);
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
        $activities = $services->flatMap(function ($service) use ($canViewInternalSeasons) {
            //Log::info('season ' . $service->name);
            return $service->seasons
                ->filter(fn($season) => $canViewInternalSeasons || !$season->internal_only)
                ->map(function ($season) use ($service) {
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
                    // from services it already filters out the promos that are not active
                    $promos = $service->activePromotions;
                    $promo = null;
                    if ($promos->count() > 0) {
                        $promo = $promos
                            ->where('type', 'time_slot')
                            ->first();
                    }
                    //dd($fieldValues);
                    return [
                        'inventory_id' => optional($inventory)->id,
                        'tag'        => $service->tag,
                        'id'      => $service->id,
                        'name'      => $service->name,
                        'season_id'      => $season->id,
                        'season'      => $season->name,
                        'promo'      => $promo,
                        'image'      => $service->file ? $service->file->path : null,
                        'pillbox_conditions'      => $service->pillbox_conditions ?? null,
                        'gift'       => filter_var($fieldValues['gift'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'minPeople'  => (int) ($fieldValues['min_people'] ?? 1),
                        'price'      => optional($season)->rake_rate_adult ?? null,
                        'start_date' => optional($season)->start_date ?? null,
                        'end_date'   => optional($season)->end_date ?? null,
                        'capacity'   => optional($service)->capacity ?? null,
                    ];
                })->filter(); // remove nulls
        });
        //dd($activities);
        //dd($teamId);
        $team = Team::select('id', 'name', 'description', 'color', 'is_published')
            ->with('file_image', 'file_logo')
            ->find($teamId);
        $countries = Country::select('id', 'name', 'phonecode')
            ->whereNotNull('phonecode')
            ->orderBy('name')
            ->get();

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canShowLogin' => Route::has('login') && app()->environment(['local', 'development']),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'activities' => $activities,
            'teams' => $teams,
            'team' => $team,
            'team_id' => $teamId ?? 0,
            'countries' => $countries,
        ]);
    }


    public function activity(Request $request)
    {
        //dd($request->all());
        $activity_id = $request->activity_id;
        $startDate = Carbon::parse($request->input('start_date', now()));

        $viewMode = $request->input('view_mode', 'week');
        $season = Season::with([
            'service',
            'service.file',
            'service.serviceFieldValues.field',
            'inventory'
        ])->findOrFail($activity_id);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $canViewUnpublished = $user && $user->can('teams_publish');
        $seasonTeam = Team::select('id', 'is_published')->find($season->team_id);
        if ($seasonTeam && !$seasonTeam->is_published && !$canViewUnpublished) {
            abort(404);
        }
        if ($season->internal_only && !$user) {
            abort(404);
        }
        if (!$season->service?->is_online_bookable && !$user) {
            abort(404);
        }
        // dd($season->service);
        /** @var Inventory|null $inventory */
        $inventory = $season->inventory;

        if (!$inventory) {
            abort(404, 'This season has no inventory.');
        }

        $fieldValues = $season->service->serviceFieldValues->mapWithKeys(function ($fv) {
            return [$fv->field->name => $this->frontActivityContent($fv->value)];
        });
        //dd($fieldValues);
        $activity = [
            'id'         => $inventory->id,
            'tag'        => $season->service?->tag,
            'service'    => $season->service?->name,
            'season'     => $season->name,
            'image'      => $season->file ? $season->file->path : null,
            'badge'      => $fieldValues['badge'] ?? null,
            'gift'       => filter_var($fieldValues['gift'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'minPeople'  => (int) ($fieldValues['min_people'] ?? 1),
            'rake_rate_adult'      => $season->rake_rate_adult ?? null,
            'rake_rate_child'      => $season->rake_rate_child ?? null,
            'start_date' => $season->start_date,
            'end_date'   => $season->end_date,
            'capacity'   => $season->service?->capacity,
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
        $team = Team::select('id', 'name', 'description', 'color')->with('file_image', 'file_logo')->find($season->team_id);
        $countries = Country::select('id', 'name', 'phonecode')
            ->whereNotNull('phonecode')
            ->orderBy('name')
            ->get();

        return Inertia::render('Activities', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'serviceId' => $season->service_id,
            'seasonId' => $season->id,
            'activityId' => $activity_id,
            'activity' => $activity,
            'schedule' => $schedule,
            'start_date' => $startDate->toDateString(),
            'view_mode' => $viewMode,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'i_adults' => $request->input('adults', 0),
            'i_children' => $request->input('children', 0),
            'availableTimes' => [],
            'team' => $team,
            'countries' => $countries,
        ]);
    }


    public function calendar(Request $request)
    {
        $activityId = $request->input('activity_id');
        $viewMode   = $request->input('view_mode', 'week');
        $startDate  = Carbon::parse($request->input('start_date', now()));
        $totalRequested = (int) $request->input('adults', 0) + (int) $request->input('children', 0);

        $days = match ($viewMode) {
            'day'   => 1,
            'week'  => 3,
            'month' => 30,
            default => 7
        };
        Log::info('viewMode ' . $viewMode);
        Log::info('days ' . $days);
        // RULE 1 — Remove today and any past date
        // if ($startDate->lte(now()->startOfDay())) {
        //     $startDate = now()->addDay()->startOfDay(); // start at tomorrow
        // }
        $endDate = (clone $startDate)->addDays($days - 1);

        $season  = Season::findOrFail($activityId);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        $canViewUnpublished = $user && $user->can('teams_publish');
        $seasonTeam = Team::select('id', 'is_published')->find($season->team_id);
        if ($seasonTeam && !$seasonTeam->is_published && !$canViewUnpublished) {
            abort(404);
        }
        if ($season->internal_only && !$user) {
            abort(404);
        }
        $service = Service::findOrFail($season->service_id);
        if (!$service->is_online_bookable && !$user) {
            abort(404);
        }

        /** @var Inventory|null $inventory */
        $inventory = Inventory::with(['inventory_type_days', 'inventory_time_slots'])
            ->where('season_id', $season->id)
            ->first();

        // outside season bounds
        if ($startDate->lt(Carbon::parse($season->start_date)) || $endDate->gt(Carbon::parse($season->end_date))) {
            return response()->json([
                'schedule' => [],
                'days'     => collect(CarbonPeriod::create($startDate, $endDate))
                    ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                    ->values(),
                'message'  => 'Selected date is outside the inventory availability period.',
            ], 200);
        }

        // booking data
        $bookingData = DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
            ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->leftJoin('inventory_overrides as io', 'io.id', '=', 'bi.inventory_override_id')
            ->where('items.inventory_id', $inventory->id)
            ->where('items.team_id', $season->team_id)
            ->whereNull('b.deleted_at')
            ->where(function ($query) {
                $query->whereNull('b.payment_window_expired_at')
                    ->orWhere('b.status_payment_id', 2);
            })
            ->whereNull('items.deleted_at')
            ->whereNull('bi.deleted_at')
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

        // allowed days from inventory


        // build schedule (still extendable with promo/closeout)
        $schedule = ServiceCalendarBuilder::build(
            $inventory,
            $season,
            $service,
            $bookingData,
            $startDate,
            $endDate,
            $totalRequested,
            null,
            null,
            false
        );

        return response()->json([
            'schedule' => $schedule,
            'days'     => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'message'  => '',
            'i_adults' => $request->input('adults', 0),
            'i_children' => $request->input('children', 0),
            'start_date' => $request->input('start_date', now()),
        ]);
    }

    public function selectedPromo(Request $request)
    {
        //return response()->json($request->all());
        $promoCode = $request->promo_code;
        $slotDate = $request->slot_date;
        $dateSlot = Carbon::parse($request->slot_date);
        // $dayName = $dateSlot->format('l'); // "Monday"
        $dayNumber = $dateSlot->dayOfWeekIso; // 1
        $slotTime = $request->slot_time;
        $activityId = $request->activity_id;
        $adults = max(0, (int) ($request->adults ?? 0));
        $children = max(0, (int) ($request->children ?? 0));

        $promo = Promotion::with('promo_day_time_slots')->where('promo_code', $promoCode)->first();
        // return response()->json($promo);
        $today = now()->toDateString();
        // 0. Booking window must include today
        if (!$promo) {
            return response()->json(['status' => false, 'promo' => 'code in valid']);;
        }

        // 1. Booking window must include today
        if ($today < $promo->booking_start_date->toDateString() || $today > $promo->booking_end_date->toDateString()) {
            return response()->json(['status' => false, 'promo' => 'period in valid']);;
        }

        // 2. Activity window must include the slot date
        $activityStart = $promo->activity_start_date;
        $activityEnd   = $promo->activity_end_date;

        if ($slotDate < $activityStart->toDateString() || $slotDate > $activityEnd->toDateString()) {
            return response()->json(['status' => false, 'promo' => 'activity in valid']);;
        }

        $season = Season::find($activityId);
        if (!$season) {
            return response()->json(['status' => false, 'promo' => 'season in valid']);
        }
        if (($season->internal_only || !$season->service?->is_online_bookable) && !auth()->user()) {
            return response()->json(['status' => false, 'promo' => 'activity unavailable']);
        }

        // 3. Service must match
        if ($promo->service_id != $season->service_id) {
            return response()->json(['status' => false, 'promo' => 'service in valid']);;
        }

        if ($promo->season_id && $promo->season_id != $season->id) {
            return response()->json(['status' => false, 'promo' => 'season in valid']);
        }

        // 4. promo day time slot must match
        if ($promo->promo_day_time_slots->isNotEmpty()) {
            $inventory = Inventory::with(['inventory_time_slots'])->where('season_id', $season->id)->first();
            $timeSlot = $inventory->inventory_time_slots?->where('start_time', $slotTime)->first();
            logger()->info('time slot ', [$timeSlot]);

            $daySlots = $promo->promo_day_time_slots->where('day_id', $dayNumber);
            $hasDayOnly = $daySlots->contains(function ($slot) {
                return $slot->time_slot_id == null;
            });
            $valid = $hasDayOnly ? true : ($timeSlot ? $daySlots->where('time_slot_id', $timeSlot->id)->first() : null);
            logger()->info('day time slot valid ', [$valid]);
            if (!$valid) {
                return response()->json(['status' => false, 'promo' => 'day time slot in valid']);
            }
        } else {
            logger()->info('nor checking promo day time slot');
        }

        // 4. Activity must match
        // if ($promo->season?->activity_id != $activityId) {
        //     return response()->json(['status' => false, 'message' => 'season in valid']);;
        // }

        try {
            $pricing = app(PricingService::class)->calculate([
                'service_id' => $season->service_id,
                'season_id' => $season->id,
                'booking_date' => $slotDate,
                'promo_code' => $promoCode,
                'pax_groups' => array_values(array_filter([
                    $adults > 0 ? ['type' => 'adult', 'count' => $adults, 'discount_id' => null] : null,
                    $children > 0 ? ['type' => 'child', 'count' => $children, 'discount_id' => null] : null,
                ])),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'promo' => $e->getMessage()]);
        }

        if (empty($pricing['applied_promotion'])) {
            return response()->json(['status' => false, 'promo' => 'Promo code could not be applied.']);
        }

        return response()->json(['status' => true, 'promo' => $promo, 'pricing' => $pricing]);
    }
    public function store(Request $request)
    {
        // TODO: validate the inventory is still available

        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'slot_date' => 'required|date',
            'slot_time' => 'required',
            'promo_code' => 'nullable|string',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'activity_id' => 'required|integer',
        ]);
        // dd($validated);
        if (config('app.debug')) {
            Log::info('Front booking store request', [
                'session_id' => session()->getId(),
                'activity_id' => $validated['activity_id'] ?? null,
                'slot_date' => $validated['slot_date'] ?? null,
                'slot_time' => $validated['slot_time'] ?? null,
                'adults' => (int) ($validated['adults'] ?? 0),
                'children' => (int) ($validated['children'] ?? 0),
                'promo_code' => $validated['promo_code'] ?? null,
            ]);
        }
        try {
            $booking = DB::transaction(function () use ($validated) {
                $activity_id = $validated['activity_id'];
                $sessionId = session()->getId();
                logger()->info('sessionId activity ' . session()->getId());
                $season = Season::findOrFail($activity_id);
                if (($season->internal_only || !$season->service?->is_online_bookable) && !auth()->user()) {
                    abort(404);
                }
                $inventory = Inventory::with(['inventory_time_slots'])->where('season_id', $season->id)->first();
                if (!$inventory) {
                    abort(404, 'This season has no inventory.');
                }
                $inventoryTypeDayId = $this->resolveInventoryTypeDayId($inventory, $validated['slot_date']);

                $adults = (int) ($validated['adults'] ?? 0);
                $children = (int) ($validated['children'] ?? 0);
                $guides = (int) ($validated['guides'] ?? 0);

                $promoCode = trim((string) ($validated['promo_code'] ?? ''));
                $promoCode = $promoCode !== '' ? $promoCode : null;
                if ($promoCode) {
                    $promo = Promotion::where('promo_code', $promoCode)
                        ->where('service_id', $season->service_id)
                        ->first();
                    if (!$promo) {
                        abort(404, 'code in valid');
                    }

                    $timeSlot = null;
                    if ($promo->promo_day_time_slots->isNotEmpty()) {
                        $timeSlot = $inventory->inventory_time_slots?->where('start_time', $validated['slot_time'])->first();
                    }
                    $dayNumber = Carbon::parse($validated['slot_date'])->dayOfWeekIso;
                    $response = $promo->validateCode($validated['slot_date'], $dayNumber, $timeSlot, $validated['activity_id']);
                    if (($response['status'] ?? false) === false) {
                        abort(404, $response['message'] ?? 'Promo code is invalid.');
                    }
                }

                $pricing = app(PricingService::class)->calculate([
                    'service_id' => $season->service_id,
                    'season_id' => $season->id,
                    'booking_date' => $validated['slot_date'],
                    'promo_code' => $promoCode,
                    'pax_groups' => array_values(array_filter([
                        $adults > 0 ? ['type' => 'adult', 'count' => $adults, 'discount_id' => null] : null,
                        $children > 0 ? ['type' => 'child', 'count' => $children, 'discount_id' => null] : null,
                        $guides > 0 ? ['type' => 'guide', 'count' => $guides, 'discount_id' => null] : null,
                    ])),
                ]);
                if ($promoCode && empty($pricing['applied_promotion'])) {
                    abort(422, 'Promo code could not be applied.');
                }

                $tot_adults = round((float) ($pricing['totals_by_type']['adult'] ?? 0), 2);
                $tot_children = round((float) ($pricing['totals_by_type']['child'] ?? 0), 2);
                $tot_guides = round((float) ($pricing['totals_by_type']['guide'] ?? 0), 2);
                $amount = round((float) ($pricing['total_price'] ?? ($tot_adults + $tot_children + $tot_guides)), 2);
                $rate_adult = (float) ($pricing['applied_rates']['adult'] ?? ($adults > 0 ? round($tot_adults / $adults, 2) : 0));
                $rate_child = (float) ($pricing['applied_rates']['child'] ?? ($children > 0 ? round($tot_children / $children, 2) : 0));
                $rate_guide = (float) ($pricing['applied_rates']['guide'] ?? ($guides > 0 ? round($tot_guides / $guides, 2) : 0));
                $promotion_id = $pricing['applied_promotion']['id'] ?? null;
                $is_voucher = (bool) $promotion_id;

                logger()->info('amount ' . $amount);
                $inventoryTimeSlot = InventoryTimeSlot::where('inventory_id', $inventory->id)->where('start_time', $validated['slot_time'])->first();
                //$inventoryTypeDay = InventoryTypeDay::where('inventory_id', $inventory->id)->first();

                if (!$inventoryTimeSlot) {
                    abort(404, 'This inventory has no time slot.');
                }

                if (config('app.debug')) {
                    Log::info('Front booking resolved context', [
                        'session_id' => $sessionId,
                        'season_id' => $season->id,
                        'service_id' => $season->service_id,
                        'team_id' => $season->team_id,
                        'inventory_id' => $inventory?->id,
                        'inventory_time_slot_id' => $inventoryTimeSlot->id,
                        'slot_date' => $validated['slot_date'],
                        'slot_time' => $validated['slot_time'],
                    ]);
                }

                $booking = Booking::where('session_id', $sessionId)->where('status_payment_id', 1)->first();
                if (!$booking) {
                    // 1. Always create a new customer for a new booking
                    $customer = Customer::create([
                        'email_address' => $validated['email'],
                        'full_name' => $validated['full_name'],
                        'contact_number' => $validated['phone'],
                    ]);

                    // 2. Create booking
                    $booking = Booking::create([
                        'user_id' => null,
                        'session_id' => $sessionId,
                        'lead_name' => $validated['full_name'],
                        'type_booking_id' => 1,
                        'status_payment_id' => 1,
                        'is_sms' => 0,
                        'is_corporate' => 0,
                        'due_date' => date('Y-m-d'),
                        'tot_paid' => 0,
                        'grand_tot_due' => $amount,
                        'reference' => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                        'customer_id' => $customer->id,
                        'team_id' => $season->team_id,
                    ]);
                } else {
                    $booking->update([
                        'grand_tot_due' => $booking->grand_tot_due + $amount,
                        'updated_at' => now(),
                    ]);

                    $customer = Customer::findOrFail($booking->customer_id);
                }

                if (config('app.debug')) {
                    Log::info('Front booking saved booking row', [
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->reference,
                        'booking_team_id' => $booking->team_id,
                        'booking_session_id' => $booking->session_id,
                        'status_payment_id' => $booking->status_payment_id,
                        'grand_tot_due' => (float) $booking->grand_tot_due,
                        'customer_id' => $booking->customer_id,
                    ]);
                }

                $bookingItem = BookingItem::create([
                    'service_id' => $season->service_id,
                    'season_id' => $season->id,
                    'inventory_id' => $inventory->id,
                    'booking_id' => $booking->id,
                    'price_adult' => $rate_adult,
                    'price_child' => $rate_child,
                    'price_guide' => $rate_guide,
                    'promotion_id' => $promotion_id,
                    'pax_adults' => $adults,
                    'pax_children' => $children,
                    'pax_guides' => 0,
                    'team_id' => $season->team_id,
                ]);

                // Admin bookings index is team-scoped on `booking_items.team_id`.
                // Front bookings historically created booking items without a team_id, causing them to "disappear" in Admin.
                if (empty($bookingItem->team_id)) {
                    $bookingItem->team_id = $season->team_id;
                    $bookingItem->save();
                }

                if (config('app.debug')) {
                    Log::info('Front booking saved booking item', [
                        'booking_item_id' => $bookingItem->id,
                        'booking_id' => $bookingItem->booking_id,
                        'booking_item_team_id' => $bookingItem->team_id,
                        'service_id' => $bookingItem->service_id,
                        'season_id' => $bookingItem->season_id,
                        'inventory_id' => $bookingItem->inventory_id,
                        'promotion_id' => $bookingItem->promotion_id,
                        'pax' => [
                            'adults' => (int) $bookingItem->pax_adults,
                            'children' => (int) $bookingItem->pax_children,
                            'guides' => (int) $bookingItem->pax_guides,
                        ],
                        'rates' => [
                            'adult' => (float) $bookingItem->price_adult,
                            'child' => (float) $bookingItem->price_child,
                            'guide' => (float) $bookingItem->price_guide,
                        ],
                    ]);
                }

                // Prepare pivot data
                $pivotData = [];

                $pivotData[$booking->customer_id] = [
                    'team_id' => $season->team_id,
                    'is_lead' => 1,
                ];

                // Sync with pivot data
                $bookingItem->customers()->sync($pivotData);


                $customerManifest = CustomerManifest::create([
                    'customer_id' => $customer->id,
                    'booking_item_id' => $bookingItem->id,
                    'team_id' => $season->team_id,
                    'is_indemnity' => 0,
                    'type' => 'adult', // hard code as adult a child would not make a booking
                    'position' => '',
                    'notes' => '',
                ]);

                $bookingDetail = BookingDetail::create([
                    'booking_item_id'     => $bookingItem->id,
                    'is_wholesaler_booking' => 0,
                    'is_needs_pickup' => 0,
                    'is_web_booking' => 0,
                    'is_videos_photos' => 0,
                    'is_exclusive_resource_use' => 0,
                    'is_override_time_slots' => 0,
                    'team_id'        => $season->team_id,
                ]);

                $bookingInventory = BookingInventory::create([
                    'booking_item_id'     => $bookingItem->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'date'           => $validated['slot_date'],
                    'adults'     => $adults,
                    'children'   => $children,
                    'guides'     => $guides,
                    'pax'            => $adults + $children,
                    'inventory_type_day_id' => $inventoryTypeDayId,
                    'inventory_time_slot_id' => $inventoryTimeSlot->id,
                    'inventory_override_id' => null,
                    'team_id'        => $season->team_id,
                ]);

                $bookingFee = BookingFee::create([
                    'booking_item_id'     => $bookingItem->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'tot_adults'     => $tot_adults,
                    'tot_children'   => $tot_children,
                    'tot_guides'     => $tot_guides,
                    'is_voucher'     => $is_voucher,
                    'voucher_code'   => $promotion_id,
                    'invoice_note'   => '',
                    'is_email_reminder' => 0,
                    'booking_note'   => '',
                    'tot_amount'     => $amount,
                    'team_id'        => $season->team_id,
                ]);
                $this->syncBookingPriceLines($bookingItem, $pricing, $validated['slot_date'], $inventoryTimeSlot->start_time);

                $template = InvoiceTemplate::where('is_default', true)->where('type_invoice_id', 1)->first();
                $invoiceTypeId = TypeInvoice::where('slug', 'std')->value('id');
                $invoice = Invoice::where('booking_id', $booking->id)
                    ->where('team_id', $season->team_id)
                    ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
                    ->first();
                $amountExVat = round(($amount / 1.15), 2);
                $vatAmount = round(($amount - $amountExVat), 2);
                if (!$invoice) {
                    $invoice = Invoice::create(
                        [
                            'booking_id'     => $booking->id,
                            'gift_voucher_id'    => null,
                            'invoice_date'   => $booking->due_date,
                            'due_date'       => $booking->due_date,
                            'subtotal'       => $amountExVat,
                            'vat'            => $vatAmount,
                            'total'          => $amount,
                            'status'         => 'draft',
                            'invoice_template_id'    => $template->id,
                            'type_invoice_id' => $template->type_invoice_id,
                            'team_id'        => $season->team_id,

                        ]
                    );
                } else {
                    $invoice->update([
                        'subtotal'       => $invoice ? $invoice->subtotal + $amountExVat : $amountExVat,
                        'vat'            => $invoice ? $invoice->vat + $vatAmount : $vatAmount,
                        'total'          => $invoice ? $invoice->total + $amount : $amount,
                    ]);
                }

                $invoiceItems = InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'pax_adults'     => (int) ($validated['adults'] ?? 0),
                    'pax_children'   => (int) ($validated['children'] ?? 0),
                    'pax_guides'     => (int) ($validated['guides'] ?? 0),
                    'price_adult'    => $tot_adults,
                    'price_child'    => $tot_children,
                    'price_guide'    => $tot_guides,
                    'team_id'        => $season->team_id,
                    'service_id'     => $season->service_id,
                    'season_id'      => $season->id,
                    'booking_inventory_id' => $bookingInventory->id,
                    'promotion_id'   => $promotion_id,
                    'wholesaler_id'  => null,
                    'discount_id'    => null,
                ]);

                return $booking;
            });

            // 4. Save to booking_inventories or other linked tables
            return response()->json([
                'success' => true,
                'booking_id' => $booking->id,
                'redirect_url' => route('front.payment.booking', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
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

    public function storeGift(Request $request)
    {
        // TODO: validate the inventory is still available
        //dd($request->all());
        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            // 'date' => 'required|date',
            // 'time' => 'required',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'activity_id' => 'required|integer',
        ]);
        try {
            $voucher = DB::transaction(function () use ($validated) {
                $activity_id = $validated['activity_id'];
                $sessionId = session()->getId();
                $season = Season::findOrFail($activity_id);
                if (($season->internal_only || !$season->service?->is_online_bookable) && !auth()->user()) {
                    abort(404);
                }
                $adultsCount = (int) ($validated['adults'] ?? 0);
                $amount_adults = $season->rake_rate_adult * $adultsCount;
                $childrenCount = (int) ($validated['children'] ?? 0);
                $amount_children = $season->rake_rate_children * $childrenCount;
                //$guidesCount = (int) ($validated['guides'] ?? 0);
                $amount_guides = 0; //  $season->rake_rate_guide * 1;
                $amount = $amount_adults + $amount_children + $amount_guides;

                //get gift voucher
                $voucher = GiftVoucher::where('session_id', $sessionId)->first();
                if (!$voucher) {
                    $voucher = GiftVoucher::create([
                        'full_name' => $validated['full_name'],
                        'email_address' => $validated['email'],
                        'contact_number' => $validated['phone'],
                        'voucher_code' => 'VREF-' . strtoupper(Str::random(6)),
                        'grand_tot_due' => $amount,
                        'note' => '',
                        'expire_date' => null,
                        'status_payment_id' => 1,
                        'paid_date' => null,
                        'redeemed' => false,
                        'redeemed_date' => null,
                        'user_id' => null,
                        'session_id' => $sessionId,
                    ]);
                }

                $voucherItem = GiftVoucherItem::create([
                    'pax_adults' => $adultsCount,
                    'pax_children' => $childrenCount,
                    'price_adult' => $amount_adults,
                    'price_child' => $amount_children,
                    'gift_voucher_id' => $voucher->id,
                    'booking_id' => null,
                    'team_id' => $season->team_id,
                    'service_id' => $season->service_id,
                    'season_id' => $season->id,
                ]);


                return $voucher;
            });

            // 4. Save to booking_inventories or other linked tables
            return response()->json([
                'success' => true,
                'voucher_id' => $voucher->id,
                'redirect_url' => route('front.payment.gift', ['gift' => $voucher->id]) //  route('payfast.checkout', ['booking' => $booking->id])
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
        $activityId = $request->activity_id;
        // return view('front.voucher', compact('activityId'));
        return Inertia::render('GiftVoucher', ['activityId' => $activityId]);
    }

    public function testBoardingPass($site)
    {

        $site = $site ? $site : 'Karkloof';
        //dd($site);
        $data = $this->getPassDetails($site);
        //$data = require base_path('sample-boarding-pass.php'); // OR build array here
        return view('pdfs.boarding-pass', ['data' => $data]);
    }

    public function testBoardingPassPdf($site)
    {

        $site = $site ? $site : 'Karkloof';
        dd($site);
        $data = $this->getPassDetails($site);
        //$data = $this->getPassDetails('Karkloof');
        $html = view('pdfs.boarding-pass', ['data' => $data])->render();
        $random = rand(1, 100);
        $dir = "tests/{$random}";
        $file = "{$dir}/boarding_pass{$random}_---------------{$random}.pdf";
        $fullPath = storage_path("app/{$file}");
        Log::info('boarding pass ' . var_export($fullPath, true));
        Log::info('boarding pass ' . var_export($file, true));
        Log::info('boarding pass ' . var_export($dir, true));
        if (!Storage::disk('local')->exists($file)) {
            Storage::disk('local')->makeDirectory($dir);

            $chromePath = config('browsershot.chrome_path');
            if (empty($chromePath)) {
                Log::warning('Browsershot chrome path not resolved; using default detection.', [
                    'env_path' => env('BROWSERSHOT_CHROME_PATH'),
                ]);
            }
            $shot = Browsershot::html($html);
            if (!empty($chromePath)) {
                $shot->setChromePath($chromePath);
            }
            $shot->format('A4')
                ->showBackground()
                ->margins(0, 0, 0, 0)
                ->scale(1.0)
                ->timeout(60000)
                ->save($fullPath);
        }

        return response()->download($fullPath);

        // Browsershot::html($html)
        //     ->setChromePath(config('browsershot.chrome_path'))
        //     ->format('A4')
        //     ->showBackground()
        //     ->margins(0, 0, 0, 0)
        //     ->showBackground()
        //     ->scale(1.0)   // auto-fit beautifully
        //     ->timeout(60000)
        //     ->save(storage_path('app/boarding-pass.pdf'));

        // return response()->download(storage_path('app/boarding-pass.pdf'));

        // $pdf = Pdf::loadView('pdfs.boarding-pass', compact('data'))
        //     ->setPaper('a4', 'portrait');

        // return $pdf->download('boarding-pass.pdf');
    }

    public function getPassDetails($value)
    {
        // dd($value);

        switch ($value) {
            case 'Karkloof':
                return [
                    'flightName' => 'Karkloof',
                    'location' => 'Cape Canopy Tour',
                    'address' => 'Address Placeholder',
                    'passenger' => 'John Doe',
                    'date' => '2025-01-14',
                    'time' => '2025-01-14',
                    'seat' => '14A',
                    'boarding_time' => '08:45',
                    'booking_no' => 'CCT-90344',
                    'flight' => 'Karkloof',
                    'passenger_name' => 'John Doe',

                    'footer' => 'All visitors and participants and participating in any activities and/or transport offered or arranged by Canopy Tours or third parties engaged by Canopy Tours by their signature of this document acknowledge that they have read & understand this indemnity, disclaimer and waiver (‘the Indemnity’) and agree to be bound by it, failing which he/she can choose not to board the transport and/or not to participate in the activities.',
                    'sections' => [

                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A short 4x4 trip to the drop off point.',
                                'A 1/2 hour fully guided zipline adventure along 12 elevated platforms, along 10 ziplines',
                                'Small exclusive groups of 8–10 people.',
                                '2x fully trained Karkloof Canopy Tour facilitators will join you on each tour.'
                            ]
                        ],

                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at 20 min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'Once the safety briefing begins late guests cannot join and the tour will be considered missed.'
                            ]
                        ],

                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Rain jacket or rain poncho on rainy days.',
                                'A small pouch or small sling bag for your phone, should you want to take photos.'
                            ]
                        ],

                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are still run in the rain and will only be stopped/cancelled if lightning in the near vicinity.',
                                'Notify the team 5 days prior to your tour if you want to move or cancel your tour.'
                            ]
                        ],

                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 120 kg per person.',
                                'Limit is based on harness size and strap circumference (size 44 pants is a good indication)',
                                'If over 120 kg but fit your body shape may still allow safe harness fit.',
                                'Minimum age: 6 years old.'
                            ]
                        ],

                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer guests to go tandem with one of our qualified guides.',
                                'Minimum age: 6 years old.'
                            ]
                        ],

                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'Small coffee shop onsite.',
                                'Wi-Fi available at the base.'
                            ]
                        ],

                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                '100% refund for cancellations 5+ days before the tour.',
                                'No refund for late arrivals or missed tours or if a guest starts a tour and does not finish.',
                                'If the company cancels you receive a full refund.',
                                'Third-party refunds handled through the agent.'
                            ]
                        ]
                    ]
                ];
                break;

            case 'Tsitsikamma':
                return [
                    'flightName' => 'Tsitsikamma',
                    'location' => 'Cape Canopy Tour',
                    'address' => '101 DARNELL STREET, STORMS RIVER VILLAGE, 6308',
                    'passenger' => 'John Doe',
                    'date' => '2025-01-14',
                    'time' => '2025-01-14',
                    'seat' => '923-251',
                    'boarding_time' => '08:45',
                    'booking_no' => '923-251',
                    'flight' => 'Tsitsikamma',
                    'passenger_name' => 'John Doe',

                    'footer' => 'All visitors and participants using any activities and/or transport offered or arranged by Stormsriver Adventures Pty Ltd (Tsitsikamma Canopy Tour), including services by third parties, acknowledge by signing this document that they have read, understood, and agree to this indemnity, disclaimer, and waiver (“the Indemnity”). By signing, participants indemnify and hold harmless Stormsriver Adventures Pty Ltd, its directors, employees, guides, agents, contractors, and affiliates against any claims, loss, injury, damage, or liability arising from participation. Participants who do not agree may choose not to participate.',

                    'sections' => [

                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A return 4x4 experience from the adventure centre to the forest.',
                                'A 2 – 3 hour fully guided forest canopy tour zipline experience along 10 elevated platforms.',
                                'Small exclusive groups of 8 people.',
                                'Two fully trained Tsitsikamma Canopy Tour guides will join you on each tour.',
                                'A personalized video (this is available to purchase as an optional extra).'
                            ]
                        ],

                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 10–15min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'If you are coming from outside the Tsitsikamma area, please factor in a 20-30 min delay on the time indicated by Google Maps due to irregular traffic conditions and possible roadworks.',
                                'Once the safety briefing begins late guests cannot join and the tour will be considered missed.'
                            ]
                        ],

                        [
                            'title' => 'What to Bring/Note',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'The activity operates in all-weather conditions. Even in the rain – just bring something warm to wear and come and enjoy a rainforest experience with us! It is in the Tsitsikamma Indigenous Forest, and always a magical experience. A rain jacket will be supplied if need be.',
                                'Comfortable clothes should be worn and any footwear that is secure on your feet and that will not fall if you are suspended in mid-air. (Closed shoes).',
                                'Long/Braided or Styled Hair: Please note that a helmet will have to fit onto your head. Hair cannot be tied on top of your head, obstructing the helmet from comfortably protecting your head.',
                                'If a device is taken on tour and falls while on the system, it cannot be retrieved.'
                            ]
                        ],

                        [
                            'title' => 'Accommodation & Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'We have a network of accommodation options and transfer operators we can recommend for use. Please reach out to our Information Centre Officers should you require assistance with arrangements on +27767313156 (WhatsApp’s welcome).',
                                'Transfers are operated by an external supplier.',
                                'For assistance with additional travel arrangements, email info2@stormsriver.com'
                            ]
                        ],

                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'We operate in all weather conditions. For safety reasons, we will cancel the tour only if severe or unsafe weather develops in the area.',
                                'We monitor the weather closely and may cancel, refund, or reschedule your tour if necessary. Any changes will be discussed with you beforehand.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],

                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 100–110 kg per person.',
                                'Limit is based on harness size and strap circumference.',
                                'For safety and comfort, limits are based on harness size and strap circumference, not body weight.',
                                'Our focus is on safely fitting you into the harness rather than the number on the scale. If you’re over 120–130 kg and your body shape fits the harness comfortably, you’re welcome to join the tour.'
                            ]
                        ],

                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer 2 tandems per tour.',
                                'Please email us at adventure@gardenroute.co.za should you or anyone in your group require a tandem.',
                                'Minimum age: 5 years old.',
                                'Children ages with us are 5 – 17 years old.',
                                'Tandems are only allowed with our guides. The first three slides help you get comfortable. If a tandem is needed, please speak to your guide.'
                            ]
                        ],

                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'You’ll receive a bottle of water during the tour to keep you refreshed. Once you’re back at the office, we’ll hand you your certificate and a meal discount voucher to enjoy at selected cafés in Storms River Village.',
                                'Wi-Fi available at the base.'
                            ]
                        ],

                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                'A 10% admin fee will be charged on all refunds processed.',
                                'Bookings cancelled 24-hours before the scheduled tour time will be refunded in full (less admin fee).',
                                'Request for refunds, cancellations, or changes to reservations made through third parties must be handled through them.',
                                'No refunds on cancellation on the day'
                            ]
                        ],
                    ]
                ];
                break;

            case 'Drakensberg':
                return [
                    'flightName' => 'Drakensberg',
                    'location' => 'Drakensberg Canopy Tour',
                    'address' => 'Drakensberg Sun Property, R160 off the R600, Cathkin Park Central Drakensberg, 3340',
                    'passenger' => 'John Doe',
                    'date' => '2025-01-14',
                    'time' => '2025-01-14',
                    'seat' => '923-251',
                    'boarding_time' => '08:45',
                    'booking_no' => '923-251',
                    'flight' => 'Drakensberg',
                    'passenger_name' => 'John Doe',
                    'footer' => 'Please note:  A Canopy Tour is an adventure activity and does come with risks. Although every effort is taken by the Canopy Tours and our staff to ensure your safety, we request that you please follow all instructions given to you by our staff either in the briefing or on the system itself.  Any non compliance to safety and or rules and polocies may result in a person being removed from the tour at anytime, at which time no refund will be given.',
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A 2,5km 4x4 drive, from base to the start point and from our finish point back to base.',
                                'A 2.5 - 3 hour fully guided forest canopy zipline adventure along 11 elevated ziplines.',
                                'Groups of up to 10 people at a time, however bigger groups can be accommidated by arrangment.',
                                '2x fully trained Drakensberg Canopy Tour facilitators will join you on each tour to ensure your safe and well looked after.',
                                'A bottle of water and a KitKat to keep you hydrated and your energy levels up.'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 20 - 30min BEFORE your tour time.',
                                'Please ensure you arrive by the requested time, as late guests may be disappointed when we are unable to accommidate them.',
                                'Once the safety briefing begins late guests might not be able to join and the tour could be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Comfortable, weather appropiate clothing and closed walking shoes.',
                                'Rain jacket on rainy or potentially rainy days.',
                                'You may bring your phone (at own risk). Please ensure you have zip up pockets or ask at reception for options.'
                            ]
                        ],

                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are mildly weather-dependent but operate in rain or shine.',
                                'The weather is monitored closely — tours may be postponed or cancelled due to severe weather and specifically if there is litening in the emediate area.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 120 -130kg kg per person.',
                                'Limit is based on harness size and strap circumference. (Mens size 44 jean should fit)',
                                'If over 120–130 kg but fit your body shape may still allow safe harness fit.',
                                'Minimum age: 6 years old. Management reserves the right to decliner a child which they think, for safety reasons should not do the Canopy Tour, regardless of age.',
                                'Anyone under the influence or suspected to be under the influence of alcohol are not allowed to participate in a Canopy Tour.  (Management reserves the right to breathalize suspected guests.',
                                'Pregnant or suspected pregnant women are not allowed to do a Canopy Tour.'
                            ]
                        ],

                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Extra snacks and drinks available before and after your tour.',
                                'We accept card and cash payments on site.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                'NO REFUNDS WILL BE GIVEN FOR THE FOLLOWING REASONS:',
                                '- No-Shows',
                                '- Not completing the tour or deciding last minute not to zipline',
                                '- Any cancellations made within 24 hours of the tour',
                                '- Late arrivals',
                                '- Postponements within 24 hours of the tour'
                            ]
                        ],
                    ]
                ];
                break;
            case 'Cape':
                return [
                    'flightName' => 'Flight 1234',
                    'location' => 'Cape Canopy Tour',
                    'address' => 'Address Placeholder',
                    'passenger' => 'John Doe',
                    'date' => '2025-01-14',
                    'time' => '2025-01-14',
                    'seat' => '14A',
                    'boarding_time' => '08:45',
                    'booking_no' => 'CCT-90344',
                    'flight' => 'FLIGHT-1234',
                    'passenger_name' => 'John Doe',
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A return 4x4 experience from through the nature reserve.',
                                'A 4–5 hour fully guided mountain top zipline adventure along 13 elevated platforms.',
                                'Small exclusive groups of 8–9 people.',
                                '2x fully trained Cape Canopy Tour facilitators will join you on each tour.',
                                'A personalized video (this is available to purchase as an optional extra).'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 10–15min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'If coming from Cape Town, please factor in an extra 30–45min for irregular traffic delays.',
                                'Once the safety briefing begins, late guests cannot join and the tour will be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Warm clothes — mountain weather is unpredictable.',
                                'Sunblock & Hat; rain jacket on rainy days.',
                                'A small light backpack (optional).',
                                'You may bring your phone or camera (at own risk).'
                            ]
                        ],
                        [
                            'title' => 'CBD Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'If booked, be ready at your transfer departure time.',
                                'Transfers are operated by an external supplier.',
                                'Contact us if you have not been contacted by the transfer driver.'
                            ]
                        ],
                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are mildly weather-dependent but operate in most conditions.',
                                'The weather is monitored closely — tours may be cancelled, refunded, or rescheduled.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 100–110 kg per person.',
                                'Limit is based on harness size and strap circumference.',
                                'If over 100–110 kg but fit, your body shape may still allow safe harness fit.',
                                'Minimum age: 5 years old.'
                            ]
                        ],
                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer 2 tandems per tour.',
                                'Tandems for guests under 50 kg only.',
                                'Email: zipline@capecanopytour.co.za for children under 9 years.',
                                'Minimum age: 5 years old.',
                                'Children under 5 may need to ride tandem with a guide.'
                            ]
                        ],
                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'Extra snacks and drinks available before and after your tour.',
                                'Wi-Fi available at the base.',
                                'Bring a card — no cash accepted.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                '100% refund for cancellations 24+ hours before the tour.',
                                'No refund for late arrivals or missed tours.',
                                'If the company cancels, you receive a full refund.',
                                'Third-party refunds handled through the agent.'
                            ]
                        ],
                    ]
                ];
                break;
            default:
                return [];
                break;
        }




















        return $data;
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

    public function calendar1(Request $request)
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
        $service = Service::findOrFail($season->service_id);

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

        $closeouts = Closeout::with('closeout_day_time_slots')->where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->get();
        logger()->info("$closeouts");
        // Build a map of blocked time slots keyed by date
        $blockedSlots = [];

        foreach ($closeouts as $c) {
            $start = Carbon::parse($c->start_date);
            $end   = Carbon::parse($c->end_date);

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $dateStr = $date->format('Y-m-d');
                $dayOfWeek = $date->dayOfWeekIso; // 1 (Mon) - 7 (Sun)

                // If no slots defined → whole day blocked
                if ($c->closeout_day_time_slots->isEmpty()) {
                    $blockedSlots[$dateStr] = 'ALL';
                } else {
                    foreach ($c->closeout_day_time_slots as $slot) {
                        if ((int)$slot->day_id === $dayOfWeek) {
                            $blockedSlots[$dateStr][$slot->time_slot_id] = true;
                        }
                    }
                }
            }
        }

        $timeSlots = $inventory->inventory_time_slots->pluck('start_time')->unique()->sort();
        $schedule = [];

        foreach ($timeSlots as $timeSlot) {
            $row = [
                'time' => $timeSlot,
                'days' => []
            ];

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $label = $date->format('D d');
                $dayOfWeek = $date->dayOfWeekIso;
                $dateStr = $date->toDateString();

                if (!in_array($dayOfWeek, $allowedDays)) {
                    $row['days'][$label] = [
                        'date' => $dateStr,
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Not an allowed day',
                    ];
                    continue;
                }

                if (isset($blockedSlots[$dateStr])) {
                    if ($blockedSlots[$dateStr] === 'ALL') {
                        // Full-day closeout
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => 0,
                            'status' => 'blocked_day',
                            'note' => 'Blocked whole day',
                        ];
                        continue;
                    }
                    // Slot-level closeout
                    $slotModel = $inventory->inventory_time_slots->firstWhere('start_time', $timeSlot);
                    if ($slotModel && isset($blockedSlots[$dateStr][$slotModel->id])) {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => 0,
                            'status' => 'blocked_slot',
                            'note' => 'Blocked slot',
                        ];
                        continue;
                    }
                }
                // once we have the booking data, we can calculate the remaining slots
                if (true) {
                    $dateTimeStr = $dateStr . '_' . $timeSlot;
                    $booked = $bookingData[$dateTimeStr]->total ?? 0;
                    $remaining = $service->capacity - $booked;
                    if ($remaining < $totalRequested) {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'status' => 'full',
                            'note' => 'Fully booked',
                        ];
                    } else {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'status' => 'available',
                            'rake_rate_adult' => $season->rake_rate_adult,
                        ];
                    }
                }
                // if we don't have the booking data, we can't calculate the remaining slots
                if (false) {
                    $row['days'][$label] = [
                        'date' => $dateStr,
                        'slots' => $service->capacity,
                        'status' => 'available',
                        'rake_rate_adult' => $season->rake_rate_adult,
                    ];
                }
            }

            $schedule[] = $row;
        }
        //dd($schedule);
        return response()->json([
            'schedule' => $schedule,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
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
            return [$fv->field->name => $this->frontActivityContent($fv->value)];
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

            return collect($dates)->map(fn(\Carbon\Carbon $d) => $d->format('D d'));
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
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'availableTimes' => [], //$availableTimes,
        ]);
    }

    private function frontActivityContent(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        $plainText = trim(preg_replace('/\s+/u', ' ', str_replace("\xc2\xa0", ' ', html_entity_decode(strip_tags($value)))));

        return in_array($plainText, ['', '.'], true) ? null : $value;
    }
}
