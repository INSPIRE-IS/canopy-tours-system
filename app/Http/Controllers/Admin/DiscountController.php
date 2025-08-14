<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'id');
        $direction = $request->get('direction', 'desc');

        $discounts = Discount::with('service')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Discounts/Index', [
            'discounts' => $discounts,
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
            'service_id' => ['required','exists:services,id'],
            'applies_to' => ['required','in:line_item,invoice'],
            'percent_off' => ['required','numeric','min:0','max:100'],
        ]);

        Discount::create($data);
        return redirect()->back()->with('message','Discount created.');
    }

    public function update(Request $request, Discount $discount)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'service_id' => ['required','exists:services,id'],
            'applies_to' => ['required','in:line_item,invoice'],
            'percent_off' => ['required','numeric','min:0','max:100'],
        ]);

        $discount->update($data);
        return redirect()->back()->with('message','Discount updated.');
    }

    public function destroy(Discount $discount)
    {
        $discount->delete();
        return redirect()->back()->with('message','Discount removed.');
    }
}
