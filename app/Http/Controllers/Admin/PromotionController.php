<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTimeSlot;
use App\Models\Promotion;
use App\Models\PromotionDayTimeSlot;
use App\Models\Season;
use App\Models\Service;
use App\Models\TypeDay;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $promos = Promotion::with('service', 'promo_day_time_slots')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('promo_code', 'like', '%' . $search . '%');
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Promotions/Index', [
            'promos' => $promos,
            'searchTerm' => $search,
            'sortColumn' => $sort,
            'sortDirection' => $direction,
            'services' => Service::select('id', 'name', 'capacity')->with('seasons.inventory.inventory_time_slots')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'promo_code' => ['required', 'string', 'max:50', 'unique:promotions,promo_code'],
            'type' => ['required', Rule::in(['percent', 'fixed_value', 'graduated', 'varied_child', 'volume_discount', 'time_slot'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'params' => ['nullable'],
            'service_id' => ['required', 'exists:services,id'],
            'season_id' => ['nullable', 'exists:seasons,id'],
            'booking_start_date' => ['required', 'date'],
            'booking_end_date' => ['required', 'date', 'after_or_equal:booking_start_date'],
            'activity_start_date' => ['required', 'date'],
            'activity_end_date' => ['required', 'date', 'after_or_equal:activity_start_date'],
            'valid_days' => ['nullable', 'string'],
            'valid_slots' => ['nullable', 'string'],
            'internal_use_only' => ['boolean'],
        ]);

        $rawValue = $request->input('value');
        $requiresValue = in_array($data['type'], ['percent', 'fixed_value', 'time_slot'], true);
        if ($requiresValue && ($rawValue === null || $rawValue === '')) {
            throw ValidationException::withMessages([
                'value' => ['A value is required for this promotion type.'],
            ]);
        }
        $data['value'] = ($rawValue === null || $rawValue === '') ? 0 : (float) $rawValue;

        if (in_array($data['type'], ['percent', 'time_slot'], true) && $data['value'] > 100) {
            throw ValidationException::withMessages([
                'value' => ['Percent cannot exceed 100.'],
            ]);
        }

        $params = $request->input('params');
        if (is_string($params)) {
            $decoded = json_decode($params, true);
            $params = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($params)) {
            $params = [];
        }
        $data['params'] = $params;

        if ($data['type'] === 'graduated' && empty($params['lookup'])) {
            throw ValidationException::withMessages([
                'params' => ['Graduated promotions require a lookup table.'],
            ]);
        }

        if ($data['type'] === 'volume_discount' && empty($params['tiers'])) {
            throw ValidationException::withMessages([
                'params' => ['Volume discounts require tiers.'],
            ]);
        }

        if ($data['type'] === 'varied_child') {
            $hasTiers = !empty($params['tiers']);
            $hasSingle = isset($params['min_adults'], $params['child_discount_percent']);
            if (!$hasTiers && !$hasSingle) {
                throw ValidationException::withMessages([
                    'params' => ['Varied child promotions require tiers or a min adult rule.'],
                ]);
            }
        }

        if ($data['type'] === 'time_slot' && empty($data['season_id'])) {
            throw ValidationException::withMessages([
                'season_id' => ['Season is required for time slot promotions.'],
            ]);
        }

        $today = Carbon::today();
        if (Carbon::parse($data['booking_start_date'])->lt($today)) {
            throw ValidationException::withMessages([
                'booking_start_date' => ['Booking start date cannot be in the past.'],
            ]);
        }

        $season = Season::where('service_id', $data['service_id'])
            ->whereDate('start_date', '<=', $data['activity_start_date'])
            ->whereDate('end_date', '>=', $data['activity_end_date'])
            ->first();

        if (!$season) {
            throw ValidationException::withMessages([
                'activity_start_date' => ['Activity dates must fall within a season for the selected service.'],
            ]);
        }

        if ($data['activity_start_date'] < $data['booking_start_date'] || $data['activity_end_date'] < $data['booking_start_date']) {
            throw ValidationException::withMessages([
                'activity_start_date' => ['Activity dates cannot be before booking start date.'],
            ]);
        }

        // TODO: check if promotion overlaps with other promotions should this be a validation rule?
        // $overlap = Promotion::where('service_id', $data['service_id'])
        //     ->where('id', '!=', $promotion->id ?? null) // ignore self on update
        //     ->where(function ($q) use ($data) {
        //         $q->whereBetween('booking_start_date', [$data['booking_start_date'], $data['booking_end_date']])
        //             ->orWhereBetween('booking_end_date', [$data['booking_start_date'], $data['booking_end_date']])
        //             ->orWhere(function ($q2) use ($data) {
        //                 $q2->where('booking_start_date', '<=', $data['booking_start_date'])
        //                     ->where('booking_end_date', '>=', $data['booking_end_date']);
        //             });
        //     })
        //     ->exists();

        // if ($overlap) {
        //     throw ValidationException::withMessages([
        //         'booking_start_date' => ['Another promotion already exists for this service in the selected booking period.'],
        //     ]);
        // }

        $promo = Promotion::create($data);
        $valid_days = array_filter(array_map('trim', explode(',', (string) $request->valid_days)));
        $valid_times = array_filter(array_map('trim', explode(',', (string) $request->valid_slots)));

        if (!empty($valid_days)) {
            foreach ($valid_days as $day) {
                $day = TypeDay::where('id', $day)->first();
                if (!$day) {
                    continue;
                }
                if (empty($valid_times)) {
                    $promo->promo_day_time_slots()->create([
                        'day_id' => $day->id,
                        'time_slot_id' => null,
                    ]);
                } else {
                    foreach ($valid_times as $time) {
                        $time = InventoryTimeSlot::where('id', $time)->first();
                        if (!$time) {
                            continue;
                        }
                        $promo->promo_day_time_slots()->create([
                            'day_id' => $day->id,
                            'time_slot_id' => $time->id,
                        ]);
                    }
                }
            }
        }


        return redirect()->back()->with('message', 'Promotion created.');
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'promo_code' => ['required', 'string', 'max:50', 'unique:promotions,promo_code,' . $promotion->id],
            'type' => ['required', Rule::in(['percent', 'fixed_value', 'graduated', 'varied_child', 'volume_discount', 'time_slot'])],
            'value' => ['nullable', 'numeric', 'min:0'],
            'params' => ['nullable'],
            'service_id' => ['required', 'exists:services,id'],
            'season_id' => ['nullable', 'exists:seasons,id'],
            'booking_start_date' => ['required', 'date'],
            'booking_end_date' => ['required', 'date', 'after_or_equal:booking_start_date'],
            'activity_start_date' => ['required', 'date'],
            'activity_end_date' => ['required', 'date', 'after_or_equal:activity_start_date'],
            'valid_days' => ['nullable', 'string'],
            'valid_slots' => ['nullable', 'string'],
            'internal_use_only' => ['boolean'],
        ]);

        $rawValue = $request->input('value');
        $requiresValue = in_array($data['type'], ['percent', 'fixed_value', 'time_slot'], true);
        if ($requiresValue && ($rawValue === null || $rawValue === '')) {
            throw ValidationException::withMessages([
                'value' => ['A value is required for this promotion type.'],
            ]);
        }
        $data['value'] = ($rawValue === null || $rawValue === '') ? 0 : (float) $rawValue;

        if (in_array($data['type'], ['percent', 'time_slot'], true) && $data['value'] > 100) {
            throw ValidationException::withMessages([
                'value' => ['Percent cannot exceed 100.'],
            ]);
        }

        $params = $request->input('params');
        if (is_string($params)) {
            $decoded = json_decode($params, true);
            $params = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($params)) {
            $params = [];
        }
        $data['params'] = $params;

        if ($data['type'] === 'graduated' && empty($params['lookup'])) {
            throw ValidationException::withMessages([
                'params' => ['Graduated promotions require a lookup table.'],
            ]);
        }

        if ($data['type'] === 'volume_discount' && empty($params['tiers'])) {
            throw ValidationException::withMessages([
                'params' => ['Volume discounts require tiers.'],
            ]);
        }

        if ($data['type'] === 'varied_child') {
            $hasTiers = !empty($params['tiers']);
            $hasSingle = isset($params['min_adults'], $params['child_discount_percent']);
            if (!$hasTiers && !$hasSingle) {
                throw ValidationException::withMessages([
                    'params' => ['Varied child promotions require tiers or a min adult rule.'],
                ]);
            }
        }

        if ($data['type'] === 'time_slot' && empty($data['season_id'])) {
            throw ValidationException::withMessages([
                'season_id' => ['Season is required for time slot promotions.'],
            ]);
        }

        $today = Carbon::today();
        if (Carbon::parse($data['booking_start_date'])->lt($today)) {
            throw ValidationException::withMessages([
                'booking_start_date' => ['Booking start date cannot be in the past.'],
            ]);
        }

        $season = Season::where('service_id', $data['service_id'])
            ->whereDate('start_date', '<=', $data['activity_start_date'])
            ->whereDate('end_date', '>=', $data['activity_end_date'])
            ->first();

        if (!$season) {
            throw ValidationException::withMessages([
                'activity_start_date' => ['Activity dates must fall within a season for the selected service.'],
            ]);
        }

        if ($data['activity_start_date'] < $data['booking_start_date'] || $data['activity_end_date'] < $data['booking_start_date']) {
            throw ValidationException::withMessages([
                'activity_start_date' => ['Activity dates cannot be before booking start date.'],
            ]);
        }

        $promotion->update($data);

        $promotion_day_time_slots = PromotionDayTimeSlot::where('promotion_id', $promotion->id)->get();
        foreach ($promotion_day_time_slots as $promotion_day_time_slot) {
            $promotion_day_time_slot->delete();
        }

        $valid_days = array_filter(array_map('trim', explode(',', (string) $request->valid_days)));
        $valid_times = array_filter(array_map('trim', explode(',', (string) $request->valid_slots)));
        if (!empty($valid_days)) {
            foreach ($valid_days as $day) {
                $day = TypeDay::where('id', $day)->first();
                if (!$day) {
                    continue;
                }
                if (empty($valid_times)) {
                    $promotion->promo_day_time_slots()->create([
                        'day_id' => $day->id,
                        'time_slot_id' => null,
                    ]);
                } else {
                    foreach ($valid_times as $time) {
                        $time = InventoryTimeSlot::where('id', $time)->first();
                        if (!$time) {
                            continue;
                        }
                        $promotion->promo_day_time_slots()->create([
                            'day_id' => $day->id,
                            'time_slot_id' => $time->id,
                        ]);
                    }
                }
            }
        }

        return redirect()->back()->with('message', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return redirect()->back()->with('message', 'Promotion removed.');
    }

    public function valid(Request $request)
    {

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'booking_date' => ['required', 'date'],
        ]);
        $today = now();
        $service = Service::find($data['service_id']);
        $bookingDate = Carbon::parse($data['booking_date']);

        // $promotions = $service->promotions()->get()->map(function ($promo) use ($bookingDate) {
        //     $promo->is_valid = $promo->is_valid; // already appended
        //     $promo->status   = $promo->status;   // active/expired/upcoming
        //     return $promo;
        // });

        // business logic
        // 1. Exclude expired activity period by returning null
        // 2. Booking window validity (today must be within booking period)
        // 3. Booking window validity (booking date must be within booking period)
        // 4. Activity window validity (chosen activity date must be within activity period)
        // 5. Promo is valid if booking date is inside booking window *and* activity date chosen is valid
        $promotions = $service->promotions
            ->map(function ($promo) use ($today, $bookingDate) {

                // Exclude expired activity period by returning null
                if ($promo->activity_start_date && $promo->activity_end_date) {
                    if ($today->gt($promo->activity_end_date)) {
                        return null;
                    }
                }

                // Booking window validity (today must be within booking period)
                $bookingValid = $today->between($promo->booking_start_date, $promo->booking_end_date) ? true : false;
                // Booking window validity (booking date must be within booking period)
                // $bookingValid = $bookingDate->between($promo->booking_start_date, $promo->booking_end_date) ? true : false;

                // Activity window validity (chosen activity date must be within activity period)
                $activityValid = true;
                if ($promo->activity_start_date && $promo->activity_end_date) {
                    $activityValid = $bookingDate->between($promo->activity_start_date, $promo->activity_end_date);
                }

                // Status only cares about booking window relative to *today*
                if ($today->lt($promo->booking_start_date)) {
                    $promo->status = 'upcoming';
                } elseif ($today->gt($promo->booking_end_date)) {
                    $promo->status = 'expired';
                } else {
                    $promo->status = 'active';
                }

                // Promo is valid if booking date is inside booking window *and* activity date chosen is valid
                $valid = $bookingValid && $activityValid;
                $promo->setAttribute('is_valid', $valid);
                // logger()->info('********************************');
                // logger()->info('promo status ' . $promo->status);
                // logger()->info('bookingValid ' . $bookingValid);
                // logger()->info('activityValid ' . $activityValid);
                // logger()->info('valid ' . $valid);
                // logger()->info('promo is_valid ' . var_export($promo->is_valid, true));
                // logger()->info('promo is_valid ' . ($promo->is_valid ? 'true' : 'false'));

                return $promo;
            })
            ->filter()
            ->values();
        //dd($promotions);
        return response()->json($promotions);
    }
}
