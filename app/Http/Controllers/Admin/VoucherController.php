<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Voucher;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;

class VoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('vouchers_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'voucher_code');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $vouchers = Voucher::with([
            'service',
            'season',
            'team',
        ])
            ->where('voucher_code', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $services = Service::with('seasons')->select('id', 'name')->get();

        return Inertia::render('Admin/Vouchers/Index', [
            'vouchers' => $vouchers,
            'services' => $services,
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
        $voucher = Voucher::create($request->all());

        return redirect()->route('admin.vouchers.index');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        $voucher->update($request->all());
        return redirect()->route('admin.vouchers.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('admin.vouchers.index');
    }
}
