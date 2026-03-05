<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventorySlotRangeRequest;
use App\Http\Requests\UpdateInventorySlotRangeRequest;
use App\Models\InventorySlotRange;
use App\Models\InventoryTimeSlot;
use App\Models\Season;
use App\Models\Service;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class InventorySlotRangesController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('inventories_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'start_date');
        $sortDirection = $request->get('direction', 'asc');

        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');

        $viewMode = $request->get('view', 'detailed');
        $slotRangesQuery = InventorySlotRange::with(['inventory.service', 'inventory.season', 'time_slot'])
            ->when($searchTerm, function ($query) use ($searchTerm) {
                $query->whereHas('inventory.service', function ($serviceQuery) use ($searchTerm) {
                    $serviceQuery->where('name', 'like', '%' . $searchTerm . '%');
                })->orWhereHas('inventory.season', function ($seasonQuery) use ($searchTerm) {
                    $seasonQuery->where('name', 'like', '%' . $searchTerm . '%');
                })->orWhere('start_date', 'like', '%' . $searchTerm . '%')
                    ->orWhere('end_date', 'like', '%' . $searchTerm . '%');
            })
            ->orderBy($sortColumn, $sortDirection);

        if ($viewMode === 'grouped') {
            $ranges = $slotRangesQuery->get();
            $grouped = $ranges->groupBy(function ($range) {
                $startDate = $range->start_date instanceof \Carbon\Carbon
                    ? $range->start_date->toDateString()
                    : (string) $range->start_date;
                $endDate = $range->end_date instanceof \Carbon\Carbon
                    ? $range->end_date->toDateString()
                    : (string) $range->end_date;

                return json_encode([
                    'inventory_id' => $range->inventory_id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'internal_only' => (int) $range->internal_only,
                    'capacity_override' => $range->capacity_override,
                    'note' => (string) ($range->note ?? ''),
                ]);
            })->map(function (Collection $items) {
                $first = $items->first();
                $firstStart = $first->start_date instanceof \Carbon\Carbon
                    ? $first->start_date->toDateString()
                    : (string) $first->start_date;
                $firstEnd = $first->end_date instanceof \Carbon\Carbon
                    ? $first->end_date->toDateString()
                    : (string) $first->end_date;
                $timeSlots = $items->map(function ($item) {
                    return $item->time_slot?->start_time;
                })->filter()->unique()->sort()->values()->all();

                return [
                    'id' => $first->id,
                    'ids' => $items->pluck('id')->values()->all(),
                    'inventory' => $first->inventory,
                    'start_date' => $firstStart,
                    'end_date' => $firstEnd,
                    'internal_only' => (bool) $first->internal_only,
                    'capacity_override' => $first->capacity_override,
                    'note' => $first->note,
                    'time_slots' => $timeSlots,
                ];
            })->values();

            $perPage = 10;
            $page = (int) $request->get('page', 1);
            $paged = $grouped->forPage($page, $perPage)->values();
            $slotRanges = new LengthAwarePaginator(
                $paged,
                $grouped->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $slotRanges = $slotRangesQuery->paginate(10);
        }

        $services = Service::with('seasons', 'seasons.inventory.inventory_time_slots')
            ->select('id', 'name')
            ->get();

        return Inertia::render('Admin/InventorySlotRanges/Index', [
            'slotRanges' => $slotRanges,
            'services' => $services,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'teamId' => $teamId,
            'viewMode' => $viewMode,
        ]);
    }

    public function store(StoreInventorySlotRangeRequest $request)
    {
        $data = $request->validated();
        $internalOnly = (bool) ($data['internal_only'] ?? false);
        $capacityOverride = $data['capacity_override'] ?? null;
        $note = $data['note'] ?? null;
        if ($capacityOverride === '') {
            $capacityOverride = null;
        }

        $season = Season::with('inventory.inventory_time_slots')->findOrFail($data['season_id']);
        if ((int) $season->service_id !== (int) $data['service_id']) {
            throw ValidationException::withMessages([
                'season_id' => 'Selected season does not belong to the selected service.',
            ]);
        }
        if (!$season->inventory) {
            throw ValidationException::withMessages([
                'season_id' => 'Selected season does not have an inventory.',
            ]);
        }

        $start = $data['start_date'];
        $end = $data['end_date'];
        if ($start > $end) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be after the start date.',
            ]);
        }

        $normalizedTimes = collect($data['time_slots'] ?? [])
            ->map(fn($time) => $this->normalizeTime($time));

        if ($normalizedTimes->contains(null)) {
            throw ValidationException::withMessages([
                'time_slots' => 'One or more time slots are invalid.',
            ]);
        }

        $timeSlots = $normalizedTimes
            ->filter()
            ->unique()
            ->values();

        if ($timeSlots->isEmpty()) {
            throw ValidationException::withMessages([
                'time_slots' => 'Please add at least one time slot.',
            ]);
        }

        $inventory = $season->inventory;

        DB::transaction(function () use ($inventory, $timeSlots, $start, $end, $internalOnly, $capacityOverride, $note) {
            $timeSlotMap = $this->buildTimeSlotMap($inventory);

            foreach ($timeSlots as $timeSlotValue) {
                $slot = $timeSlotMap[$timeSlotValue] ?? $timeSlotMap[substr($timeSlotValue, 0, 5)] ?? null;
                if (!$slot) {
                    $slot = InventoryTimeSlot::create([
                        'inventory_id' => $inventory->id,
                        'start_time' => $timeSlotValue,
                        'is_override_only' => true,
                    ]);
                    $timeSlotMap[$slot->start_time] = $slot;
                    $timeSlotMap[substr($slot->start_time, 0, 5)] = $slot;
                }

                $this->assertNoOverlap($inventory->id, $slot->id, $start, $end);

                InventorySlotRange::create([
                    'inventory_id' => $inventory->id,
                    'time_slot_id' => $slot->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'internal_only' => $internalOnly,
                    'capacity_override' => $capacityOverride,
                    'note' => $note,
                    'team_id' => auth()->user()->team_id,
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);
            }
        });

        if ($request->wantsJson()) {
            $timeSlots = $inventory->inventory_time_slots()
                ->orderBy('start_time')
                ->get(['id', 'start_time', 'is_override_only']);
            return response()->json([
                'time_slots' => $timeSlots,
            ], 201);
        }

        return redirect()->route('admin.inventory-slot-ranges.index');
    }

    public function update(UpdateInventorySlotRangeRequest $request, InventorySlotRange $inventory_slot_range)
    {
        $data = $request->validated();
        $internalOnly = (bool) ($data['internal_only'] ?? false);
        $capacityOverride = $data['capacity_override'] ?? null;
        $note = $data['note'] ?? null;
        if ($capacityOverride === '') {
            $capacityOverride = null;
        }

        $season = Season::with('inventory.inventory_time_slots')->findOrFail($data['season_id']);
        if ((int) $season->service_id !== (int) $data['service_id']) {
            throw ValidationException::withMessages([
                'season_id' => 'Selected season does not belong to the selected service.',
            ]);
        }
        if (!$season->inventory) {
            throw ValidationException::withMessages([
                'season_id' => 'Selected season does not have an inventory.',
            ]);
        }

        $start = $data['start_date'];
        $end = $data['end_date'];
        if ($start > $end) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be after the start date.',
            ]);
        }

        $timeSlots = null;
        if (!empty($data['time_slots'])) {
            $normalizedTimes = collect($data['time_slots'] ?? [])
                ->map(fn($time) => $this->normalizeTime($time));
            if ($normalizedTimes->contains(null)) {
                throw ValidationException::withMessages([
                    'time_slots' => 'One or more time slots are invalid.',
                ]);
            }
            $timeSlots = $normalizedTimes->filter()->unique()->values()->all();
            if (empty($timeSlots)) {
                throw ValidationException::withMessages([
                    'time_slots' => 'Please add at least one time slot.',
                ]);
            }
        } else {
            $normalizedTime = $this->normalizeTime($data['time_slot'] ?? null);
            if (!$normalizedTime) {
                throw ValidationException::withMessages([
                    'time_slot' => 'Time slot is required.',
                ]);
            }
            $timeSlots = [$normalizedTime];
        }

        $inventory = $season->inventory;
        $oldInventoryId = $inventory_slot_range->inventory_id;

        DB::transaction(function () use ($inventory, $inventory_slot_range, $timeSlots, $start, $end, $internalOnly, $capacityOverride, $note, $data) {
            $timeSlotMap = $this->buildTimeSlotMap($inventory);
            $incomingIds = $data['ids'] ?? [];
            $ignoreIds = !empty($incomingIds)
                ? array_values(array_filter(array_map('intval', $incomingIds)))
                : [$inventory_slot_range->id];

            $slots = [];
            foreach ($timeSlots as $timeSlotValue) {
                $slot = $timeSlotMap[$timeSlotValue] ?? $timeSlotMap[substr($timeSlotValue, 0, 5)] ?? null;
                if (!$slot) {
                    $slot = InventoryTimeSlot::create([
                        'inventory_id' => $inventory->id,
                        'start_time' => $timeSlotValue,
                        'is_override_only' => true,
                    ]);
                    $timeSlotMap[$slot->start_time] = $slot;
                    $timeSlotMap[substr($slot->start_time, 0, 5)] = $slot;
                }
                $slots[] = $slot;
            }

            foreach ($slots as $slot) {
                $this->assertNoOverlap($inventory->id, $slot->id, $start, $end, $ignoreIds);
            }

            if (!empty($ignoreIds)) {
                InventorySlotRange::whereIn('id', $ignoreIds)
                    ->where('inventory_id', $inventory->id)
                    ->delete();
            }

            foreach ($slots as $slot) {
                InventorySlotRange::create([
                    'inventory_id' => $inventory->id,
                    'time_slot_id' => $slot->id,
                    'start_date' => $start,
                    'end_date' => $end,
                    'internal_only' => $internalOnly,
                    'capacity_override' => $capacityOverride,
                    'note' => $note,
                    'team_id' => auth()->user()->team_id,
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);
            }
        });

        $this->cleanupUnusedOverrideSlots($oldInventoryId);
        $this->cleanupUnusedOverrideSlots($inventory->id);

        return redirect()->route('admin.inventory-slot-ranges.index');
    }

    public function destroy(Request $request, InventorySlotRange $inventory_slot_range)
    {
        abort_if(Gate::denies('inventories_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventoryId = $inventory_slot_range->inventory_id;
        $ids = $request->input('ids', []);

        if (!empty($ids)) {
            InventorySlotRange::where('inventory_id', $inventoryId)
                ->whereIn('id', $ids)
                ->delete();
        } else {
            $inventory_slot_range->delete();
        }

        $this->cleanupUnusedOverrideSlots($inventoryId);

        return back();
    }

    protected function normalizeTime(?string $time): ?string
    {
        if (!$time) {
            return null;
        }

        $time = trim($time);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        return null;
    }

    protected function buildTimeSlotMap($inventory): array
    {
        $map = [];
        foreach ($inventory->inventory_time_slots as $slot) {
            $map[$slot->start_time] = $slot;
            $map[substr($slot->start_time, 0, 5)] = $slot;
        }

        return $map;
    }

    protected function assertNoOverlap(int $inventoryId, int $timeSlotId, string $start, string $end, int|array|null $ignoreIds = null): void
    {
        $overlapQuery = InventorySlotRange::where('inventory_id', $inventoryId)
            ->where('time_slot_id', $timeSlotId)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($query) use ($start, $end) {
                        $query->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            });

        if ($ignoreIds) {
            $ids = is_array($ignoreIds) ? $ignoreIds : [$ignoreIds];
            $overlapQuery->whereNotIn('id', $ids);
        }

        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'Slot ranges cannot overlap for the same time slot.',
            ]);
        }
    }

    protected function cleanupUnusedOverrideSlots(int $inventoryId): void
    {
        $usedSlotIds = InventorySlotRange::where('inventory_id', $inventoryId)
            ->pluck('time_slot_id')
            ->unique()
            ->all();

        InventoryTimeSlot::where('inventory_id', $inventoryId)
            ->where('is_override_only', true)
            ->when(!empty($usedSlotIds), function ($query) use ($usedSlotIds) {
                $query->whereNotIn('id', $usedSlotIds);
            }, function ($query) {
                $query->whereRaw('1 = 1');
            })
            ->delete();
    }
}

