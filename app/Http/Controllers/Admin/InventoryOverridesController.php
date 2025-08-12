<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyInventoryOverrideRequest;
use App\Http\Requests\StoreInventoryOverrideRequest;
use App\Http\Requests\UpdateInventoryOverrideRequest;
use App\Models\Inventory;
use App\Models\InventoryOverride;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InventoryOverridesController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('inventory_overrides_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventoryOverrides = InventoryOverride::with(['inventory', 'team'])->get();

        return view('Admin.inventoryOverrides.index', compact('inventoryOverrides'));
    }

    public function create()
    {
        abort_if(Gate::denies('inventory_override_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventories = Inventory::pluck('start_time', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('Admin.inventoryOverrides.create', compact('inventories'));
    }

    public function store(StoreInventoryOverrideRequest $request)
    {
        $inventoryOverride = InventoryOverride::create($request->all());

        return redirect()->route('Admin.inventory-overrides.index');
    }

    public function edit(InventoryOverride $inventoryOverride)
    {
        abort_if(Gate::denies('inventory_override_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventories = Inventory::pluck('start_time', 'id')->prepend(trans('global.pleaseSelect'), '');

        $inventoryOverride->load('inventory', 'team');

        return view('Admin.inventoryOverrides.edit', compact('inventories', 'inventoryOverride'));
    }

    public function update(UpdateInventoryOverrideRequest $request, InventoryOverride $inventoryOverride)
    {
        $inventoryOverride->update($request->all());

        return redirect()->route('Admin.inventory-overrides.index');
    }

    public function show(InventoryOverride $inventoryOverride)
    {
        abort_if(Gate::denies('inventory_override_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventoryOverride->load('inventory', 'team');

        return view('Admin.inventoryOverrides.show', compact('inventoryOverride'));
    }

    public function destroy(InventoryOverride $inventoryOverride)
    {
        abort_if(Gate::denies('inventory_override_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventoryOverride->delete();

        return back();
    }
}
