<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\PromotionDayTimeSlot;
use App\Models\Service;
use App\Models\TypeDay;
use Illuminate\Http\Request;
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
            'services' => Service::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'promo_code' => ['required', 'string', 'max:50', 'unique:promotions,promo_code'],
            'type' => ['required', 'in:percent,fixed_value,graduated,varied_child'],
            'value' => ['required', 'numeric', 'min:0'],
            'service_id' => ['required', 'exists:services,id'],
            'booking_start_date' => ['required', 'date'],
            'booking_end_date' => ['required', 'date', 'after_or_equal:booking_start_date'],
            'activity_start_date' => ['required', 'date'],
            'activity_end_date' => ['required', 'date', 'after_or_equal:activity_start_date'],
            //'valid_days' => ['nullable', 'string'],
            'internal_use_only' => ['boolean'],
        ]);
        // check overlapping promo windows
        $overlap = Promotion::where('service_id', $data['service_id'])
            ->where('id', '!=', $promotion->id ?? null) // ignore self on update
            ->where(function ($q) use ($data) {
                $q->whereBetween('booking_start_date', [$data['booking_start_date'], $data['booking_end_date']])
                    ->orWhereBetween('booking_end_date', [$data['booking_start_date'], $data['booking_end_date']])
                    ->orWhere(function ($q2) use ($data) {
                        $q2->where('booking_start_date', '<=', $data['booking_start_date'])
                            ->where('booking_end_date', '>=', $data['booking_end_date']);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'booking_start_date' => ['Another promotion already exists for this service in the selected booking period.'],
            ]);
        }

        $promo = Promotion::create($data);
        $valid_days = explode(',', $request->valid_days);
        foreach ($valid_days as $day) {
            $day = TypeDay::where('id', $day)->first();
            if (!$day) {
                continue;
            }

            $promo->promo_day_time_slots()->create([
                'day_id' => $day->id,
                'time_slot_id' => null,
            ]);
        }


        return redirect()->back()->with('message', 'Promotion created.');
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'promo_code' => ['required', 'string', 'max:50', 'unique:promotions,promo_code,' . $promotion->id],
            'type' => ['required', 'in:percent,fixed_value,graduated,varied_child'],
            'value' => ['required', 'numeric', 'min:0'],
            'service_id' => ['required', 'exists:services,id'],
            'booking_start_date' => ['required', 'date'],
            'booking_end_date' => ['required', 'date', 'after_or_equal:booking_start_date'],
            'activity_start_date' => ['required', 'date'],
            'activity_end_date' => ['required', 'date', 'after_or_equal:activity_start_date'],
            //'valid_days' => ['nullable', 'string'],
            'internal_use_only' => ['boolean'],
        ]);

        $promotion->update($data);

        $promotion_day_time_slots = PromotionDayTimeSlot::where('promotion_id', $promotion->id)->get();
        foreach ($promotion_day_time_slots as $promotion_day_time_slot) {
            $promotion_day_time_slot->delete();
        }

        $valid_days = explode(',', $request->valid_days);
        foreach ($valid_days as $day) {
            $day = TypeDay::where('id', $day)->first();
            if (!$day) {
                continue;
            }

            $promotion->promo_day_time_slots()->create([
                'day_id' => $day->id,
                'time_slot_id' => null,
            ]);
        }

        return redirect()->back()->with('message', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return redirect()->back()->with('message', 'Promotion removed.');
    }
}
