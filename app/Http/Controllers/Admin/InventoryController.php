<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\InventoryTimeSlot;
use App\Models\InventoryTypeDay;
use App\Models\Season;
use App\Models\Service;
use App\Models\TypeDay;
use App\Models\TypeTimeSlot;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('inventories_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'season_id');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        //$seasons = Season::with(['service', 'updated_by', 'team'])
        $inventories = Inventory::with(['service', 'season', 'inventory_type_days.type_day', 'inventory_time_slots', 'inventory_slot_ranges.time_slot', 'team'])
            ->where('season_id', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        // dd($inventories->items()[0]);
        $services = Service::select('name', 'id')->get();
        $seasons = Season::select('name', 'id', 'service_id')->get();
        $inventorySeasonIdsByService = Inventory::select('service_id', 'season_id')
            ->get()
            ->groupBy('service_id')
            ->map(fn($rows) => $rows->pluck('season_id')->unique()->values())
            ->toArray();
        $day_slots = TypeDay::select('name', 'id')->get();
        //$time_slots = TypeTimeSlot::select('name', 'id')->get();
        //dd($services);
        return Inertia::render('Admin/Inventories/Index', [

            'services' => $services,
            'seasons' => $seasons,
            'inventories' => $inventories,
            'day_slots' => $day_slots,
            'inventorySeasonIdsByService' => $inventorySeasonIdsByService,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }


    public function store(StoreInventoryRequest $request)
    {
        //dd($request->all());
        // plan with days if we select 1,2,3,4,5 this is saved then for the period we take these into consideration for the time slots and only do mon to friday
        // plan with time slots we capture these and then they go to every day we will use blockout to exclude days times and periods
        $normalizedTimes = collect($request->time_slots ?? [])
            ->map(function ($slot) {
                $slotTime = is_array($slot) ? ($slot['start_time'] ?? '') : $slot;
                $slotTime = trim((string) $slotTime);
                if ($slotTime === '') {
                    return null;
                }
                return strlen($slotTime) >= 5 ? substr($slotTime, 0, 5) : $slotTime;
            })
            ->filter()
            ->values();

        if ($normalizedTimes->count() !== $normalizedTimes->unique()->count()) {
            throw ValidationException::withMessages([
                'time_slots' => ['Duplicate time slots are not allowed.'],
            ]);
        }

        DB::transaction(function () use ($request) {
            $data = $request->except(['day_slots', 'time_slots', 'allow_slot_cutoff_hours', 'allow_slot_funneling']);
            $data['created_by_id'] = auth()->id();
            $data['team_id'] = auth()->user()->team_id;

            $inventory = Inventory::create($data);

            // Clear existing related slots
            $inventory->inventory_type_days()->delete();
            $inventory->inventory_time_slots()->delete();

            // Prepare new day slot records
            $daySlotRecords = collect($request->day_slots)->map(fn($typeDayId) => [
                'inventory_id' => $inventory->id,
                'type_day_id' => $typeDayId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            // Prepare new time slot records (adjust depending on format)
            $timeSlotRecords = collect($request->time_slots)->map(function ($slot) use ($inventory) {
                $slotTime = is_array($slot) ? $slot['start_time'] : $slot;
                $startTime = strlen($slotTime) === 5 ? $slotTime . ':00' : $slotTime;
                return [
                    'inventory_id' => $inventory->id,
                    'start_time' => $startTime,
                    'is_override_only' => false,
                    //'end_time' => is_array($slot) && isset($slot['end_time']) ? $slot['end_time'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            // Bulk insert
            InventoryTypeDay::insert($daySlotRecords);
            InventoryTimeSlot::insert($timeSlotRecords);
        });

        return redirect()->route('admin.inventories.index');
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory)
    {

        try {
            // dd($request);
            $normalizedTimes = collect($request->time_slots ?? [])
                ->map(function ($slot) {
                    $slotTime = is_array($slot) ? ($slot['start_time'] ?? '') : $slot;
                    $slotTime = trim((string) $slotTime);
                    if ($slotTime === '') {
                        return null;
                    }
                    return strlen($slotTime) >= 5 ? substr($slotTime, 0, 5) : $slotTime;
                })
                ->filter()
                ->values();

            if ($normalizedTimes->count() !== $normalizedTimes->unique()->count()) {
                throw ValidationException::withMessages([
                    'time_slots' => ['Duplicate time slots are not allowed.'],
                ]);
            }

            DB::transaction(function () use ($request, $inventory) {
                $data = $request->except(['day_slots', 'time_slots', 'allow_slot_cutoff_hours', 'allow_slot_funneling']);
                $data['updated_by_id'] = auth()->id();
                $data['team_id'] = auth()->user()->team_id;

                $inventory->update($data);
                //$inventory->time_slots()->sync($request->input('time_slots', []));
                // Clear existing related day slots we need these but technically use over rides currently
                $inventory->inventory_type_days()->delete();
                // Prepare new day slot records
                $daySlotRecords = collect($request->day_slots)->map(fn($typeDayId) => [
                    'inventory_id' => $inventory->id,
                    'type_day_id' => $typeDayId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                $inventory->inventory_time_slots()->where('is_override_only', false)->delete();
                // Prepare new time slot records (adjust depending on format)
                $timeSlotRecords = collect($request->time_slots)->map(function ($slot) use ($inventory) {
                    $slot_time = is_array($slot) ? $slot['start_time'] : $slot;
                    $start_time = strlen($slot_time) === 5 ? $slot_time . ':00' : $slot_time;
                    return [
                        'inventory_id' => $inventory->id,
                        'start_time' => $start_time,
                        'is_override_only' => false,
                        //'end_time' => is_array($slot) && isset($slot['end_time']) ? $slot['end_time'] : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                // Bulk insert
                InventoryTypeDay::insert($daySlotRecords);
                InventoryTimeSlot::insert($timeSlotRecords);
            });

            // return redirect()->route('admin.inventories.index');
        } catch (\Throwable $e) {
            // Log the full error for debugging
            $data = $request->except(['day_slots', 'time_slots', 'allow_slot_cutoff_hours', 'allow_slot_funneling']);
            Log::error('Inventory failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $data,
            ]);
            return back()->with([
                'error' => 'Something went wrong while saving your Inventory. Please try again.',
            ]);
        }
    }

    public function show(Inventory $inventory)
    {
        abort_if(Gate::denies('inventory_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventory->load('service', 'season', 'time_slots', 'updated_by', 'team');

        return view('Admin.inventories.show', compact('inventory'));
    }

    public function destroy(Inventory $inventory)
    {
        abort_if(Gate::denies('inventories_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //abort_if(Gate::denies('inventories_delete'), 403);
        $inventory->delete();

        return back();
    }
}
