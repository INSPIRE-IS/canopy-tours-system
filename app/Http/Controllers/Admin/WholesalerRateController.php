<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesalerRate;
use App\Models\Season;
use App\Models\Wholesaler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WholesalerRateController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $rates = WholesalerRate::with(['season','wholesaler'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('wholesaler', function ($qq) use ($search) {
                    $qq->where('name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/WholesalerRates/Index', [
            'rates' => $rates,
            'searchTerm' => $search,
            'sortColumn' => $sort,
            'sortDirection' => $direction,
            'seasons' => Season::select('id','name')->orderBy('name')->get(),
            'wholesalers' => Wholesaler::select('id','name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'wholesaler_id' => ['required','exists:wholesalers,id'],
            'season_id' => ['required','exists:seasons,id'],
            'price_adult' => ['required','numeric','min:0'],
            'price_child' => ['nullable','numeric','min:0'],
            'price_guide' => ['nullable','numeric','min:0'],
        ]);

        WholesalerRate::create($data);
        return redirect()->back()->with('message','Wholesaler rate created.');
    }

    public function update(Request $request, WholesalerRate $wholesalerRate)
    {
        $data = $request->validate([
            'wholesaler_id' => ['required','exists:wholesalers,id'],
            'season_id' => ['required','exists:seasons,id'],
            'price_adult' => ['required','numeric','min:0'],
            'price_child' => ['nullable','numeric','min:0'],
            'price_guide' => ['nullable','numeric','min:0'],
        ]);

        $wholesalerRate->update($data);
        return redirect()->back()->with('message','Wholesaler rate updated.');
    }

    public function destroy(WholesalerRate $wholesalerRate)
    {
        $wholesalerRate->delete();
        return redirect()->back()->with('message','Wholesaler rate removed.');
    }
}
