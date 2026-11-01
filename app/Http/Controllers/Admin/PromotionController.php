<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryTimeSlot;
use App\Models\Promotion;
use App\Models\PromotionDayTimeSlot;
use App\Models\Season;
use App\Models\Service;
use App\Models\TypeDay;
use App\Support\PromotionSeasonWindow;
use App\Support\VariedChildPromotion;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('promotions_access'), 403);
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
            'type' => ['required', Rule::in(['percent', 'fixed_value', 'graduated', 'varied_child', 'volume_discount', 'pax_exact_discount', 'time_slot'])],
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
        if ($data['type'] === 'volume_discount') {
            $params['tiers'] = $this->validateVolumeDiscountTiers($params['tiers'] ?? null);
            $data['params'] = $params;
        }

        if ($data['type'] === 'pax_exact_discount') {
            $params['rules'] = $this->validateExactPaxDiscountRules($params['rules'] ?? null);
            $data['params'] = $params;
        }

        if ($data['type'] === 'varied_child') {
            $params = $this->validateVariedChildParams($params);
            $data['params'] = $params;
        }

        if ($data['type'] === 'fixed_value') {
            $mode = (string) ($params['fixed_value_mode'] ?? 'booking');
            if (!in_array($mode, ['booking', 'per_pax', 'one_adult', 'one_child', 'each_adult', 'each_child', 'each_adult_child'], true)) {
                throw ValidationException::withMessages([
                    'params' => ['Fixed value mode is invalid.'],
                ]);
            }
            $params['fixed_value_mode'] = $mode;
            $data['params'] = $params;
        }

        if ($data['type'] === 'time_slot' && empty($data['season_id'])) {
            throw ValidationException::withMessages([
                'season_id' => ['Season is required for time slot promotions.'],
            ]);
        }

        $this->validateActivitySeasonCoverage($data);

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
            'type' => ['required', Rule::in(['percent', 'fixed_value', 'graduated', 'varied_child', 'volume_discount', 'pax_exact_discount', 'time_slot'])],
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
        if ($data['type'] === 'volume_discount') {
            $params['tiers'] = $this->validateVolumeDiscountTiers($params['tiers'] ?? null);
            $data['params'] = $params;
        }

        if ($data['type'] === 'pax_exact_discount') {
            $params['rules'] = $this->validateExactPaxDiscountRules($params['rules'] ?? null);
            $data['params'] = $params;
        }

        if ($data['type'] === 'varied_child') {
            $params = $this->validateVariedChildParams($params);
            $data['params'] = $params;
        }

        if ($data['type'] === 'fixed_value') {
            $mode = (string) ($params['fixed_value_mode'] ?? 'booking');
            if (!in_array($mode, ['booking', 'per_pax', 'one_adult', 'one_child', 'each_adult', 'each_child', 'each_adult_child'], true)) {
                throw ValidationException::withMessages([
                    'params' => ['Fixed value mode is invalid.'],
                ]);
            }
            $params['fixed_value_mode'] = $mode;
            $data['params'] = $params;
        }

        if ($data['type'] === 'time_slot' && empty($data['season_id'])) {
            throw ValidationException::withMessages([
                'season_id' => ['Season is required for time slot promotions.'],
            ]);
        }

        $this->validateActivitySeasonCoverage($data);

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

    private function validateActivitySeasonCoverage(array $data): void
    {
        $seasons = Season::query()
            ->where('service_id', $data['service_id'])
            ->get(['id', 'start_date', 'end_date', 'internal_only']);

        $errors = PromotionSeasonWindow::validationErrors(
            $seasons,
            $data['type'],
            $data['season_id'] ?? null,
            $data['activity_start_date'],
            $data['activity_end_date'],
            (bool) ($data['internal_use_only'] ?? false)
        );

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateVariedChildParams(array $params): array
    {
        try {
            return VariedChildPromotion::normalizeParams($params);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'params' => [$exception->getMessage()],
            ]);
        }
    }

    private function validateExactPaxDiscountRules($rawRules): array
    {
        if (is_string($rawRules)) {
            $decodedRules = json_decode($rawRules, true);
            $rawRules = is_array($decodedRules) ? $decodedRules : [];
        }

        if (is_object($rawRules)) {
            $rawRules = (array) $rawRules;
        }

        if (!is_array($rawRules) || empty($rawRules)) {
            throw ValidationException::withMessages([
                'params' => ['Exact pax discounts require at least one pax rule.'],
            ]);
        }

        $rules = [];
        foreach ($rawRules as $pax => $rule) {
            if (!is_array($rule) || !ctype_digit((string) $pax) || (int) $pax < 1) {
                throw ValidationException::withMessages([
                    'params' => ['Exact pax discount rules must use a valid pax number.'],
                ]);
            }

            $type = (string) ($rule['type'] ?? 'percent');
            $value = $rule['value'] ?? null;
            if (!in_array($type, ['percent', 'fixed_value'], true)) {
                throw ValidationException::withMessages([
                    'params' => ['Exact pax discount rule type must be % or R.'],
                ]);
            }

            if ($value === null || $value === '' || !is_numeric($value) || (float) $value < 0) {
                throw ValidationException::withMessages([
                    'params' => ['Exact pax discount rules require a numeric value.'],
                ]);
            }

            if ($type === 'percent' && (float) $value > 100) {
                throw ValidationException::withMessages([
                    'params' => ['Exact pax discount percent cannot exceed 100.'],
                ]);
            }

            $rules[(string) (int) $pax] = [
                'type' => $type,
                'value' => (float) $value,
            ];
        }

        ksort($rules, SORT_NUMERIC);

        return $rules;
    }

    private function validateVolumeDiscountTiers($rawTiers): array
    {
        if (!is_array($rawTiers) || empty($rawTiers)) {
            throw ValidationException::withMessages([
                'params' => ['Volume discounts require at least one tier.'],
            ]);
        }

        $tiers = [];
        foreach ($rawTiers as $tier) {
            $pax = $tier['pax'] ?? null;
            $type = (string) ($tier['type'] ?? 'percent');
            $value = $tier['value'] ?? null;

            if ($pax === null || $pax === '' || !is_numeric($pax) || (int) $pax < 1) {
                throw ValidationException::withMessages([
                    'params' => ['Volume discount tiers require a valid pax number.'],
                ]);
            }

            if (!in_array($type, ['percent', 'fixed_value'], true)) {
                throw ValidationException::withMessages([
                    'params' => ['Volume discount tier type must be % or R.'],
                ]);
            }

            if ($value === null || $value === '' || !is_numeric($value) || (float) $value < 0) {
                throw ValidationException::withMessages([
                    'params' => ['Volume discount tiers require a numeric value.'],
                ]);
            }

            if ($type === 'percent' && (float) $value > 100) {
                throw ValidationException::withMessages([
                    'params' => ['Volume discount percent cannot exceed 100.'],
                ]);
            }

            $tiers[] = [
                'pax' => (int) $pax,
                'type' => $type,
                'value' => (float) $value,
            ];
        }

        usort($tiers, fn ($a, $b) => $a['pax'] <=> $b['pax']);

        return $tiers;
    }
}
