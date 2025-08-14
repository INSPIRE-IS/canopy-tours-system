<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $promos = Promotion::with('service')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                  ->orWhere('promo_code','like','%'.$search.'%');
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Promotions/Index', [
            'promos' => $promos,
            'searchTerm' => $search,
            'sortColumn' => $sort,
            'sortDirection' => $direction,
            'services' => Service::select('id','name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'promo_code' => ['required','string','max:50','unique:promotions,promo_code'],
            'type' => ['required','in:percent,fixed_value,graduated,varied_child'],
            'value' => ['required','numeric','min:0'],
            'service_id' => ['required','exists:services,id'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'valid_days' => ['nullable','string'],
            'internal_use_only' => ['boolean'],
        ]);

        Promotion::create($data);
        return redirect()->back()->with('message','Promotion created.');
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'promo_code' => ['required','string','max:50','unique:promotions,promo_code,'.$promotion->id],
            'type' => ['required','in:percent,fixed_value,graduated,varied_child'],
            'value' => ['required','numeric','min:0'],
            'service_id' => ['required','exists:services,id'],
            'start_date' => ['required','date'],
            'end_date' => ['required','date','after_or_equal:start_date'],
            'valid_days' => ['nullable','string'],
            'internal_use_only' => ['boolean'],
        ]);

        $promotion->update($data);
        return redirect()->back()->with('message','Promotion updated.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();
        return redirect()->back()->with('message','Promotion removed.');
    }
}
