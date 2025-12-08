<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\Closeout;
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
use App\Models\Promotion;
use App\Models\Season;
use App\Models\Service;
use App\Models\Team;
use App\Services\Calendar\ServiceCalendarBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PHPUnit\Framework\Constraint\IsFalse;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'activePromotions',
        ]);

        if ($teamId) {
            $services->where('team_id', $teamId);
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
        $team = Team::select('id', 'name', 'description', 'color')->with('file_image', 'file_logo')->find($teamId);
        //dd($team);

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'activities' => $activities,
            'teams' => $teams,
            'team' => $team,
            'team_id' => $teamId ?? 0,
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
            'start_date' => $request->input('start_date', now()),
            'availableTimes' => [],
            'team' => $team,
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
        if ($startDate->lte(now()->startOfDay())) {
            $startDate = now()->addDay()->startOfDay(); // start at tomorrow
        }
        $endDate = (clone $startDate)->addDays($days - 1);

        $season  = Season::findOrFail($activityId);
        $service = Service::findOrFail($season->service_id);

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
            ->where('items.inventory_id', $activityId)
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
        $schedule = app(\App\Services\Calendar\ServiceCalendarBuilder::class)
            ->build($inventory, $season, $service, $bookingData, $startDate, $endDate, $totalRequested);

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

        // 3. Service must match
        if ($promo->service_id != $activityId) {
            return response()->json(['status' => false, 'promo' => 'service in valid']);;
        }

        // 4. promo day time slot must match
        if ($promo->promo_day_time_slots->isNotEmpty()) {
            $inventory = Inventory::with(['inventory_time_slots'])->where('season_id', $promo->season_id)->first();
            $timeSlot = $inventory->inventory_time_slots?->where('start_time', $slotTime)->first();
            logger()->info('time slot ', [$timeSlot]);

            $valid = $promo->promo_day_time_slots
                ->where('day_id', $dayNumber)
                ->where('time_slot_id', $timeSlot->id)->first();
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

        return response()->json(['status' => true, 'promo' => $promo]);
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
        try {
            $booking = DB::transaction(function () use ($validated) {
                $activity_id = $validated['activity_id'];
                $sessionId = session()->getId();
                logger()->info('sessionId activity ' . session()->getId());
                $season = Season::findOrFail($activity_id);
                $inventory = Inventory::with(['inventory_time_slots'])->where('season_id', $season->id)->first();
                //$FullNameOfDay = Carbon::parse($validated['slot_date'])->format('l'); // e.g., Monday
                //$ShortNameOfDay = Carbon::parse($validated['slot_date'])->format('D'); // e.g., Mon
                $isoNameOfDay = Carbon::parse($validated['slot_date'])->format('N'); // iso week starts on Monday
                //$numericNameOfDay = Carbon::parse($validated['slot_date'])->format('w'); // week starts on Sunday
                // lookup booking
                $adults = (int) ($validated['adults'] ?? 0);
                $tot_adults = $season->rake_rate_adult * $adults;
                $children = (int) ($validated['children'] ?? 0);
                $tot_children = $season->rake_rate_child * $children;
                $guides = (int) ($validated['guides'] ?? 0);
                $tot_guides = $season->rake_rate_guide * $guides;
                $amount = $tot_adults + $tot_children + $tot_guides;
                logger()->info('amount ' . $amount);
                $promoCode = $validated['promo_code'];
                $promotion_id = null;
                $is_voucher = false;
                if ($promoCode !== null) {
                    $promo = Promotion::where('promo_code', $promoCode)->first();
                    if (!$promo) {
                        abort(404, 'code in valid');
                    } else {
                        $timeSlot = null;
                        if ($promo->promo_day_time_slots->isNotEmpty()) {
                            $timeSlot = $inventory->inventory_time_slots?->where('start_time', $validated['slot_time'])->first();
                        }
                        logger()->info('time slot ', [$timeSlot]);
                        $dateSlot = Carbon::parse($validated['slot_date']);
                        // $dayName = $dateSlot->format('l'); // "Monday"
                        $dayNumber = $dateSlot->dayOfWeekIso; // 1
                        $response = $promo->validateCode($validated['slot_date'], $dayNumber, $timeSlot, $validated['activity_id']);
                        if ($response['status'] == false) {
                            abort(404, $response['message']);
                        }
                    }
                    $is_voucher = true;
                    $promotion_id = $promo->id;
                    if ($promo->type == 'time_slot') {
                        $tot_adults = $adults > 0 ? $tot_adults * (1 - $promo->value / 100) : 0;
                        $tot_children = $children > 0 ? $tot_children * (1 - $promo->value / 100) : 0;
                        $tot_guides = $guides > 0 ? $tot_guides * (1 - $promo->value / 100) : 0;
                    }
                    if ($promo->type == 'fixed_value') {
                        $tot_adults = $adults > 0 ? $tot_adults -  $promo->value : 0;
                        $tot_children = $children > 0 ? $tot_children -  $promo->value : 0;
                        $tot_guides = $guides > 0 ? $tot_guides -  $promo->value : 0;
                    }
                    // if($promo->type == 'graduated'){
                    //     $tot_adults = $adults > 0 ? $tot_adults -  $promo->value : 0;
                    //     $tot_children = $children > 0 ? $tot_children -  $promo->value : 0;
                    //     $tot_guides = $guides > 0 ? $tot_guides -  $promo->value : 0;
                    // }
                    if ($promo->type == 'percent') {
                        $tot_adults = $adults > 0 ? $tot_adults -  $promo->value : 0;
                        $tot_children = $children > 0 ? $tot_children -  $promo->value : 0;
                        $tot_guides = $guides > 0 ? $tot_guides -  $promo->value : 0;
                    }
                    // if($promo->type == 'varied_child'){
                    //     $tot_adults = $adults > 0 ? $tot_adults -  $promo->value : 0;
                    //     $tot_children = $children > 0 ? $tot_children -  $promo->value : 0;
                    //     $tot_guides = $guides > 0 ? $tot_guides -  $promo->value : 0;
                    // }
                    // if($promo->type == 'volume_discount'){
                    //     $tot_adults = $adults > 0 ? $tot_adults -  $promo->value : 0;
                    //     $tot_children = $children > 0 ? $tot_children -  $promo->value : 0;
                    //     $tot_guides = $guides > 0 ? $tot_guides -  $promo->value : 0;
                    // }
                    $amount = $tot_adults + $tot_children + $tot_guides;
                }

                if (!$inventory) {
                    abort(404, 'This season has no inventory.');
                }
                $inventoryTimeSlot = InventoryTimeSlot::where('inventory_id', $inventory->id)->where('start_time', $validated['slot_time'])->first();
                //$inventoryTypeDay = InventoryTypeDay::where('inventory_id', $inventory->id)->first();

                if (!$inventoryTimeSlot) {
                    abort(404, 'This inventory has no time slot.');
                }

                $booking = Booking::where('session_id', $sessionId)->where('status_payment_id', 1)->first();
                if (!$booking) {
                    // 1. Create or find customer
                    $customer = Customer::firstOrCreate([
                        'email_address' => $validated['email'],
                    ], [
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

                $bookingItem = BookingItem::updateOrCreate([
                    'id' => $booking->id,
                    'service_id' => $season->service_id,
                    'season_id' => $season->id,
                    'inventory_id' => $inventory->id,
                    'booking_id' => $booking->id,
                    'price_adult' => $season->rake_rate_adult,
                    'price_child' => $season->rake_rate_child,
                    'price_guide' => $season->rake_rate_guide,
                    'promotion_id' => $promotion_id,
                    'pax_adults' => $adults,
                    'pax_children' => $children,
                    'pax_guides' => 0,
                ]);

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
                    'inventory_type_day_id' => $isoNameOfDay,
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
                $template = InvoiceTemplate::where('is_default', true)->where('type_invoice_id', 1)->first();
                $invoice = Invoice::where('booking_id', $booking->id)->where('team_id', $season->team_id)->first();
                if (!$invoice) {
                    $invoice = Invoice::create(
                        [
                            'booking_id'     => $booking->id,
                            'gift_voucher_id'    => null,
                            'invoice_date'   => $booking->due_date,
                            'due_date'       => $booking->due_date,
                            'subtotal'       => $amount,
                            'vat'            => $amount * 0.15,
                            'total'          => $amount * 1.15,
                            'status'         => 'draft',
                            'invoice_template_id'    => $template->id,
                            'team_id'        => $season->team_id,

                        ]
                    );
                } else {
                    $invoice->update([
                        'subtotal'       => $invoice ? $invoice->subtotal + $amount : $amount,
                        'vat'            => $invoice ? $invoice->vat + $amount * 0.15 : $amount * 0.15,
                        'total'          => $invoice ? $invoice->total + $amount * 1.15 : $amount * 1.15,
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
        dd($request->all());
        $activityId = $request->activity_id;
        // return view('front.voucher', compact('activityId'));
        return Inertia::render('GiftVoucher', ['activityId' => $activityId]);
    }

    public function testBoardingPass(Request $request)
    {
        $data = [
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

        //$data = require base_path('sample-boarding-pass.php'); // OR build array here
        return view('pdfs.boarding-pass', ['data' => $data]);
    }

    public function testBoardingPassPdf(Request $request)
    {
        $data = [
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

        $pdf = Pdf::loadView('pdfs.boarding-pass', compact('data'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('boarding-pass.pdf');
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
}
