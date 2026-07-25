<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wholesaler;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WholesalerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('wholesalers_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $wholesalers = Wholesaler::with([
            'created_by',
            'updated_by',
            'team',
        ])
            ->where('team_id', auth()->user()->team_id)
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        return Inertia::render('Admin/Wholesalers/Index', [
            'wholesalers' => $wholesalers,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'fieldValues' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('wholesalers', 'name')->where('team_id', auth()->user()->team_id)],
            'reg_no' => ['nullable', 'string', 'max:255'],
            'vat_no' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $data['team_id'] = auth()->user()->team_id;
        $data['created_by_id'] = auth()->id();

        Wholesaler::create($data);
        return redirect()->route('admin.wholesalers.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wholesaler $wholesaler)
    {
        abort_if((int) $wholesaler->team_id !== (int) auth()->user()->team_id, 403);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('wholesalers', 'name')
                    ->where('team_id', auth()->user()->team_id)
                    ->ignore($wholesaler->id),
            ],
            'reg_no' => ['nullable', 'string', 'max:255'],
            'vat_no' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $data['team_id'] = auth()->user()->team_id;
        $data['updated_by_id'] = auth()->id();

        $wholesaler->update($data);
        return redirect()->route('admin.wholesalers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wholesaler $wholesaler)
    {
        abort_if((int) $wholesaler->team_id !== (int) auth()->user()->team_id, 403);

        $wholesaler->delete();
        return redirect()->route('admin.wholesalers.index');
    }
}
