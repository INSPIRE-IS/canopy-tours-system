<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wholesaler;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;

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
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $wholesalers = Wholesaler::with([
            'created_by',
            'updated_by',
            'team',
        ])
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
        //dd($request->all());
        $wholesaler = Wholesaler::create($request->all());
        return redirect()->route('admin.wholesalers.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wholesaler $wholesaler)
    {
        $wholesaler->update($request->all());
        return redirect()->route('admin.wholesalers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wholesaler $wholesaler)
    {
        $wholesaler->delete();
        return redirect()->route('admin.wholesalers.index');
    }
}
