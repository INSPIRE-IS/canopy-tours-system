<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\Closeout;
use App\Models\CloseoutDayTimeSlot;
use App\Models\InventorySlotRange;
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
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
        $inventoryIds = $inventories->getCollection()->pluck('id')->all();
        $bookedSlots = empty($inventoryIds)
            ? []
            : DB::table('booking_items as items')
                ->join('bookings as b', 'b.id', '=', 'items.booking_id')
                ->join('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
                ->whereIn('items.inventory_id', $inventoryIds)
                ->where('items.team_id', auth()->user()->team_id)
                ->whereNull('items.deleted_at')
                ->whereNull('b.deleted_at')
                ->whereNull('bi.deleted_at')
                ->groupBy('items.inventory_id', 'bi.date', 'bi.inventory_time_slot_id')
                ->select([
                    'items.inventory_id',
                    'bi.date',
                    'bi.inventory_time_slot_id',
                    DB::raw('SUM(bi.pax) as pax'),
                    DB::raw('COUNT(DISTINCT items.id) as bookings_count'),
                ])
                ->get();
        // dd($inventories->items()[0]);
        $services = Service::select('name', 'id')->get();
        $seasons = Season::select('name', 'id', 'service_id')->get();
        $inventorySeasonIdsByService = Inventory::select('service_id', 'season_id')
            ->get()
            ->groupBy('service_id')
            ->map(fn($rows) => $rows->pluck('season_id')->unique()->values())
            ->toArray();
        $day_slots = TypeDay::select('name', 'id')->get();
        $closeouts = Closeout::with('closeout_day_time_slots')
            ->get(['id', 'service_id', 'season_id', 'name', 'reason', 'start_date', 'end_date']);
        //$time_slots = TypeTimeSlot::select('name', 'id')->get();
        //dd($services);
        return Inertia::render('Admin/Inventories/Index', [

            'services' => $services,
            'seasons' => $seasons,
            'inventories' => $inventories,
            'day_slots' => $day_slots,
            'closeouts' => $closeouts,
            'bookedSlots' => $bookedSlots,
            'inventorySeasonIdsByService' => $inventorySeasonIdsByService,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function availabilityPreview(Request $request, Inventory $inventory)
    {
        abort_if(Gate::denies('inventories_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $inventory->team_id !== (int) auth()->user()->team_id, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $inventory->load(['service', 'season', 'inventory_type_days', 'inventory_time_slots']);

        $season = $inventory->season;
        $service = $inventory->service;

        if (!$season || !$service) {
            return response()->json([
                'schedule' => [],
                'days' => [],
                'message' => 'Inventory is missing a linked service or season.',
            ], 422);
        }

        $seasonStart = Carbon::parse($season->start_date)->startOfDay();
        $seasonEnd = Carbon::parse($season->end_date)->startOfDay();
        $requestedStart = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now('Africa/Johannesburg')->startOfDay();
        $requestedEnd = $request->filled('to')
            ? Carbon::parse($request->input('to'))->startOfDay()
            : (clone $requestedStart)->addDays(6);

        if ($requestedEnd->lt($requestedStart)) {
            throw ValidationException::withMessages([
                'to' => 'End date must be after the start date.',
            ]);
        }

        if ($requestedStart->diffInDays($requestedEnd) > 30) {
            $requestedEnd = (clone $requestedStart)->addDays(30);
        }

        $startDate = $requestedStart->lt($seasonStart) ? $seasonStart : $requestedStart;
        $endDate = $requestedEnd->gt($seasonEnd) ? $seasonEnd : $requestedEnd;

        if ($startDate->gt($endDate)) {
            return response()->json([
                'inventory' => [
                    'id' => $inventory->id,
                    'service' => $service->name,
                    'season' => $season->name,
                    'season_start_date' => $seasonStart->toDateString(),
                    'season_end_date' => $seasonEnd->toDateString(),
                ],
                'schedule' => [],
                'days' => [],
                'message' => 'Selected preview range is outside the inventory season.',
            ]);
        }

        $rawBookingData = DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->where('items.inventory_id', $inventory->id)
            ->where('items.team_id', auth()->user()->team_id)
            ->whereNull('b.deleted_at')
            ->whereNull('items.deleted_at')
            ->whereNull('bi.deleted_at')
            ->whereBetween('bi.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'bi.date',
                'its.start_time as time',
                DB::raw('SUM(bi.pax) as total'),
            ])
            ->groupBy('bi.date', 'its.start_time')
            ->get();

        $bookingData = $rawBookingData->keyBy(fn($row) => $row->date . '_' . $row->time);

        $schedule = app(\App\Services\Calendar\ServiceCalendarBuilder::class)
            ->build($inventory, $season, $service, $bookingData, $startDate, $endDate, 0, null, null, true);

        return response()->json([
            'inventory' => [
                'id' => $inventory->id,
                'service' => $service->name,
                'season' => $season->name,
                'season_start_date' => $seasonStart->toDateString(),
                'season_end_date' => $seasonEnd->toDateString(),
            ],
            'schedule' => $schedule,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(Carbon $date) => [
                    'label' => $date->format('D d'),
                    'date' => $date->toDateString(),
                    'display' => $date->format('D d M'),
                ])
                ->values(),
            'message' => '',
        ]);
    }


    public function store(StoreInventoryRequest $request)
    {
        //dd($request->all());
        // plan with days if we select 1,2,3,4,5 this is saved then for the period we take these into consideration for the time slots and only do mon to friday
        // plan with time slots we capture these and then they go to every day we will use blockout to exclude days times and periods
        $normalizedTimes = $this->normalizedInventoryTimes($request->time_slots ?? []);

        if ($normalizedTimes->count() !== $normalizedTimes->unique()->count()) {
            throw ValidationException::withMessages([
                'time_slots' => ['Duplicate time slots are not allowed.'],
            ]);
        }
        $normalizedTimes = $normalizedTimes->unique()->sort()->values();

        DB::transaction(function () use ($request, $normalizedTimes) {
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
            $timeSlotRecords = $normalizedTimes->map(function ($slotTime) use ($inventory) {
                return [
                    'inventory_id' => $inventory->id,
                    'start_time' => $slotTime . ':00',
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
            $normalizedTimes = $this->normalizedInventoryTimes($request->time_slots ?? []);

            if ($normalizedTimes->count() !== $normalizedTimes->unique()->count()) {
                throw ValidationException::withMessages([
                    'time_slots' => ['Duplicate time slots are not allowed.'],
                ]);
            }
            $normalizedTimes = $normalizedTimes->unique()->sort()->values();

            DB::transaction(function () use ($request, $inventory, $normalizedTimes) {
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

                InventoryTypeDay::insert($daySlotRecords);
                $this->syncBaseInventoryTimeSlots($inventory, $normalizedTimes);
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

    private function normalizedInventoryTimes($timeSlots)
    {
        return collect($timeSlots)
            ->map(function ($slot) {
                $slotTime = is_array($slot) ? ($slot['start_time'] ?? '') : $slot;
                $slotTime = trim((string) $slotTime);
                if ($slotTime === '') {
                    return null;
                }

                return strlen($slotTime) >= 5 ? substr($slotTime, 0, 5) : $slotTime;
            })
            ->filter()
            ->sort()
            ->values();
    }

    private function syncBaseInventoryTimeSlots(Inventory $inventory, $normalizedTimes): void
    {
        $existingSlotsByTime = $inventory->inventory_time_slots()
            ->withTrashed()
            ->where('is_override_only', false)
            ->get()
            ->groupBy(fn($slot) => substr((string) $slot->start_time, 0, 5));

        $keptSlotIds = collect();

        foreach ($normalizedTimes as $time) {
            $slotsForTime = $existingSlotsByTime
                ->get($time, collect())
                ->sortBy(fn($slot) => ($slot->trashed() ? '1' : '0') . '-' . str_pad((string) $slot->id, 10, '0', STR_PAD_LEFT))
                ->values();

            $slot = $slotsForTime->first();
            if ($slot) {
                $slot->restore();
                $slot->forceFill([
                    'start_time' => $time . ':00',
                    'is_override_only' => false,
                    'updated_at' => now(),
                ])->save();
            } else {
                $slot = InventoryTimeSlot::create([
                    'inventory_id' => $inventory->id,
                    'start_time' => $time . ':00',
                    'is_override_only' => false,
                ]);
            }

            $keptSlotIds->push($slot->id);

            $slotsForTime->slice(1)->each(function (InventoryTimeSlot $duplicate) use ($slot) {
                $this->mergeDuplicateInventoryTimeSlotReferences($duplicate, $slot);
                $duplicate->delete();
            });
        }

        $inventory->inventory_time_slots()
            ->where('is_override_only', false)
            ->when(
                $keptSlotIds->isNotEmpty(),
                fn($query) => $query->whereNotIn('id', $keptSlotIds->all())
            )
            ->delete();
    }

    private function mergeDuplicateInventoryTimeSlotReferences(InventoryTimeSlot $from, InventoryTimeSlot $to): void
    {
        if ((int) $from->id === (int) $to->id) {
            return;
        }

        DB::table('booking_inventories')
            ->where('inventory_time_slot_id', $from->id)
            ->update(['inventory_time_slot_id' => $to->id]);

        DB::table('closeout_day_time_slots')
            ->where('time_slot_id', $from->id)
            ->update(['time_slot_id' => $to->id]);

        DB::table('promotion_day_time_slots')
            ->where('time_slot_id', $from->id)
            ->update(['time_slot_id' => $to->id]);

        DB::table('inventory_slot_ranges')
            ->where('time_slot_id', $from->id)
            ->update(['time_slot_id' => $to->id]);
    }

    public function schedule(Request $request, Inventory $inventory)
    {
        abort_if(Gate::denies('inventories_schedule'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if((int) $inventory->team_id !== (int) auth()->user()->team_id, Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $request->validate([
            'action' => 'required|in:close,open,admin_only,capacity,clear',
            'internal_only' => 'nullable|boolean',
            'confirm_booked' => 'nullable|boolean',
            'capacity_override' => 'nullable|integer|min:0',
            'cells' => 'required|array|min:1',
            'cells.*.date' => 'required|date',
            'cells.*.slot_id' => 'required|integer',
            'cells.*.status' => 'nullable|string',
        ]);

        if (($data['action'] ?? null) === 'capacity' && ($data['capacity_override'] ?? null) === null) {
            throw ValidationException::withMessages([
                'capacity_override' => 'Capacity is required when setting a capacity override.',
            ]);
        }
        if (($data['action'] ?? null) === 'capacity') {
            $closedCapacityCells = collect($data['cells'] ?? [])
                ->filter(fn($cell) => in_array($cell['status'] ?? null, ['closed', 'closeout'], true));
            if ($closedCapacityCells->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'cells' => 'Capacity can only be set on open, date override, or admin-only slots. Open the slot first if it is currently closed.',
                ]);
            }
        }
        $inventory->load(['service', 'season', 'inventory_time_slots', 'inventory_type_days']);
        $season = $inventory->season;
        if (!$season) {
            throw ValidationException::withMessages([
                'inventory' => 'Selected inventory does not have a season.',
            ]);
        }

        $slotIds = $inventory->inventory_time_slots->pluck('id')->map(fn($id) => (int) $id)->all();
        $cells = collect($data['cells'])
            ->map(function ($cell) use ($season, $slotIds) {
                $date = Carbon::parse($cell['date'])->toDateString();
                if ($date < Carbon::parse($season->start_date)->toDateString() || $date > Carbon::parse($season->end_date)->toDateString()) {
                    throw ValidationException::withMessages([
                        'cells' => 'All selected dates must be inside the inventory season.',
                    ]);
                }

                if (!in_array((int) $cell['slot_id'], $slotIds, true)) {
                    throw ValidationException::withMessages([
                        'cells' => 'All selected time slots must belong to the selected inventory.',
                    ]);
                }

                return [
                    'date' => $date,
                    'slot_id' => (int) $cell['slot_id'],
                    'status' => $cell['status'] ?? null,
                ];
            })
            ->unique(fn($cell) => $cell['date'] . '|' . $cell['slot_id'])
            ->values();

        $bookedCells = $this->bookedScheduleCells($inventory, $cells);
        if ($bookedCells->isNotEmpty() && empty($data['confirm_booked'])) {
            throw ValidationException::withMessages([
                'confirm_booked' => 'Some selected slots already have bookings. Confirm booked-slot changes to continue.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $cleared = 0;
        $skipped = 0;
        $manualSkipped = 0;

        DB::transaction(function () use ($data, $inventory, $cells, &$created, &$updated, &$cleared, &$skipped, &$manualSkipped) {
            foreach ($cells as $cell) {
                if ($data['action'] === 'close') {
                    $created += $this->createSchedulerCloseout($inventory, $cell);
                    continue;
                }

                if ($data['action'] === 'clear') {
                    $clearResult = $this->clearSchedulerAdjustment($inventory, $cell);
                    $cleared += $clearResult['cleared'];
                    $skipped += $clearResult['skipped'];
                    $manualSkipped += $clearResult['manual_skipped'];
                    continue;
                }

                $result = $this->upsertSchedulerDateOverride($inventory, $cell, $data);
                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            }
        });

        if ($data['action'] === 'clear') {
            $messages = ["Cleared: {$cleared} scheduler adjustment" . ($cleared === 1 ? '' : 's') . '.'];
            if ($manualSkipped > 0) {
                $messages[] = "{$manualSkipped} manual record" . ($manualSkipped === 1 ? ' was' : 's were') . ' not changed.';
            }
            if ($skipped > 0) {
                $messages[] = "{$skipped} selected cell" . ($skipped === 1 ? ' had' : 's had') . ' no scheduler adjustment to clear.';
            }

            return back()->with([
                'success' => implode(' ', $messages),
            ]);
        }

        return back()->with([
            'success' => "Schedule updated. Created: {$created}, updated: {$updated}, cleared: {$cleared}, skipped: {$skipped}.",
        ]);
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

    private function bookedScheduleCells(Inventory $inventory, $cells)
    {
        $dates = $cells->pluck('date')->unique()->all();
        $slotIds = $cells->pluck('slot_id')->unique()->all();
        $selected = $cells->mapWithKeys(fn($cell) => [$cell['date'] . '|' . $cell['slot_id'] => true]);

        return DB::table('booking_items as items')
            ->join('bookings as b', 'b.id', '=', 'items.booking_id')
            ->join('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->where('items.inventory_id', $inventory->id)
            ->where('items.team_id', auth()->user()->team_id)
            ->whereNull('items.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('bi.deleted_at')
            ->whereIn('bi.date', $dates)
            ->whereIn('bi.inventory_time_slot_id', $slotIds)
            ->select('bi.date', 'bi.inventory_time_slot_id', DB::raw('SUM(bi.pax) as pax'))
            ->groupBy('bi.date', 'bi.inventory_time_slot_id')
            ->get()
            ->filter(fn($row) => $selected->has($row->date . '|' . $row->inventory_time_slot_id));
    }

    private function createSchedulerCloseout(Inventory $inventory, array $cell): int
    {
        $date = $cell['date'];
        $dayId = (int) Carbon::parse($date)->dayOfWeekIso;

        $existing = Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('closeout_day_time_slots', function ($query) use ($dayId, $cell) {
                $query->where('day_id', $dayId)
                    ->where('time_slot_id', $cell['slot_id']);
            })
            ->exists();

        if ($existing) {
            return 0;
        }

        $closeout = Closeout::create([
            'service_id' => $inventory->service_id,
            'season_id' => $inventory->season_id,
            'name' => '[Scheduler] Close slot',
            'reason' => '[Scheduler] Close selected slot',
            'start_date' => $date,
            'end_date' => $date,
            'team_id' => auth()->user()->team_id,
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id(),
        ]);

        CloseoutDayTimeSlot::create([
            'closeout_id' => $closeout->id,
            'day_id' => $dayId,
            'time_slot_id' => $cell['slot_id'],
            'team_id' => auth()->user()->team_id,
        ]);

        return 1;
    }

    private function upsertSchedulerDateOverride(Inventory $inventory, array $cell, array $data): string
    {
        $date = $cell['date'];
        $this->removeCloseoutCellFromCoveringCloseouts($inventory, $cell);
        $this->ensureSchedulerDateOverrideBaseline($inventory, $date);

        $existing = InventorySlotRange::where('inventory_id', $inventory->id)
            ->where('time_slot_id', $cell['slot_id'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($existing && !str_starts_with((string) $existing->note, '[Scheduler]')) {
            return 'skipped';
        }

        $internalOnly = ($data['action'] === 'admin_only') || (bool) ($data['internal_only'] ?? false);

        $payload = [
            'inventory_id' => $inventory->id,
            'time_slot_id' => $cell['slot_id'],
            'start_date' => $date,
            'end_date' => $date,
            'internal_only' => $internalOnly,
            'capacity_override' => $data['action'] === 'capacity' ? ($data['capacity_override'] ?? null) : null,
            'note' => match ($data['action']) {
                'open' => $internalOnly ? '[Scheduler] Open selected slot as admin-only' : '[Scheduler] Open selected slot',
                'admin_only' => '[Scheduler] Make selected slot admin-only',
                'capacity' => $internalOnly ? '[Scheduler] Set capacity override and keep admin-only' : '[Scheduler] Set capacity override',
                default => '[Scheduler] Schedule adjustment',
            },
            'team_id' => auth()->user()->team_id,
            'updated_by_id' => auth()->id(),
        ];

        if ($existing) {
            $existing->update($payload);
            return 'updated';
        }

        InventorySlotRange::create($payload + [
            'created_by_id' => auth()->id(),
        ]);

        return 'created';
    }

    private function removeCloseoutCellFromCoveringCloseouts(Inventory $inventory, array $cell): int
    {
        $date = $cell['date'];
        $dayId = (int) Carbon::parse($date)->dayOfWeekIso;
        $adjusted = 0;

        $coveringCloseouts = Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function ($query) use ($dayId, $cell) {
                $query->whereDoesntHave('closeout_day_time_slots')
                    ->orWhereHas('closeout_day_time_slots', function ($slotQuery) use ($dayId, $cell) {
                        $slotQuery->where('day_id', $dayId)
                            ->where('time_slot_id', $cell['slot_id']);
                    });
            })
            ->with('closeout_day_time_slots')
            ->get();

        foreach ($coveringCloseouts as $closeout) {
            $slots = $closeout->closeout_day_time_slots;
            $isAllDayCloseout = $slots->isEmpty();

            $startDate = Carbon::parse($closeout->getRawOriginal('start_date') ?: $closeout->start_date)->toDateString();
            $endDate = Carbon::parse($closeout->getRawOriginal('end_date') ?: $closeout->end_date)->toDateString();
            $targetDate = Carbon::parse($date);

            if ($startDate < $date) {
                $this->cloneCloseoutSegment(
                    $closeout,
                    $startDate,
                    $targetDate->copy()->subDay()->toDateString(),
                    $slots
                );
            }

            if ($date < $endDate) {
                $this->cloneCloseoutSegment(
                    $closeout,
                    $targetDate->copy()->addDay()->toDateString(),
                    $endDate,
                    $slots
                );
            }

            $sameDaySlots = $slots->filter(function ($slot) use ($dayId) {
                return (int) $slot->day_id === $dayId;
            })->reject(function ($slot) use ($cell) {
                return (int) $slot->time_slot_id === (int) $cell['slot_id'];
            })->values();

            if ($isAllDayCloseout) {
                $sameDaySlots = $inventory->inventory_time_slots
                    ->reject(fn($slot) => (int) $slot->id === (int) $cell['slot_id'])
                    ->map(fn($slot) => (object) [
                        'day_id' => $dayId,
                        'time_slot_id' => $slot->id,
                        'team_id' => $closeout->team_id,
                    ])
                    ->values();
            }

            if ($sameDaySlots->isNotEmpty()) {
                $this->cloneCloseoutSegment($closeout, $date, $date, $sameDaySlots);
            }

            $closeout->delete();
            $adjusted++;
        }

        return $adjusted;
    }

    private function cloneCloseoutSegment(Closeout $source, string $startDate, string $endDate, $slots): Closeout
    {
        $closeout = Closeout::create([
            'service_id' => $source->service_id,
            'season_id' => $source->season_id,
            'name' => $source->name,
            'reason' => $source->reason,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'team_id' => $source->team_id,
            'created_by_id' => $source->created_by_id,
            'updated_by_id' => auth()->id(),
        ]);

        foreach ($slots as $slot) {
            CloseoutDayTimeSlot::create([
                'closeout_id' => $closeout->id,
                'day_id' => $slot->day_id,
                'time_slot_id' => $slot->time_slot_id,
                'team_id' => $slot->team_id,
            ]);
        }

        return $closeout;
    }

    private function ensureSchedulerDateOverrideBaseline(Inventory $inventory, string $date): void
    {
        $existingDateOverride = InventorySlotRange::where('inventory_id', $inventory->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($existingDateOverride) {
            return;
        }

        $allowedDays = $inventory->inventory_type_days->pluck('type_day_id')->map(fn($id) => (int) $id)->all();
        $dayId = (int) Carbon::parse($date)->dayOfWeekIso;
        if (!in_array($dayId, $allowedDays, true)) {
            return;
        }

        $baseSlots = $inventory->inventory_time_slots->filter(fn($slot) => !$slot->is_override_only);
        foreach ($baseSlots as $slot) {
            InventorySlotRange::create([
                'inventory_id' => $inventory->id,
                'time_slot_id' => $slot->id,
                'start_date' => $date,
                'end_date' => $date,
                'internal_only' => false,
                'capacity_override' => null,
                'note' => '[Scheduler] Preserve base schedule',
                'team_id' => auth()->user()->team_id,
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);
        }
    }

    private function clearSchedulerAdjustment(Inventory $inventory, array $cell): array
    {
        $date = $cell['date'];
        $cleared = 0;
        $manualSkipped = $this->hasManualScheduleRecordForCell($inventory, $cell) ? 1 : 0;
        $isBaseCell = $this->isBaseScheduledCell($inventory, $cell);
        $restoreClosedCell = false;

        $ranges = InventorySlotRange::where('inventory_id', $inventory->id)
            ->where('time_slot_id', $cell['slot_id'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('note', 'like', '[Scheduler]%')
            ->get();

        foreach ($ranges as $range) {
            $note = (string) $range->note;

            if (str_contains($note, 'Open selected slot')) {
                $range->delete();
                $cleared++;

                // Opening a closed slot may have split/removed a closeout. After old
                // scheduler closeouts are cleared below, put this exact cell back to closed.
                $restoreClosedCell = true;
                continue;
            }

            if ($isBaseCell && !str_contains($note, 'Preserve base schedule')) {
                $range->update([
                    'internal_only' => false,
                    'capacity_override' => null,
                    'note' => '[Scheduler] Preserve base schedule',
                    'updated_by_id' => auth()->id(),
                ]);
                $cleared++;
                continue;
            }

            if (!$isBaseCell || str_contains($note, 'Preserve base schedule')) {
                $range->delete();
                $cleared++;
            }
        }

        $dayId = (int) Carbon::parse($date)->dayOfWeekIso;
        $schedulerCloseouts = Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function ($query) {
                $query->where('name', 'like', '[Scheduler]%')
                    ->orWhere('reason', 'like', '[Scheduler]%');
            })
            ->with('closeout_day_time_slots')
            ->get();

        foreach ($schedulerCloseouts as $closeout) {
            $slots = $closeout->closeout_day_time_slots()
                ->where('day_id', $dayId)
                ->where('time_slot_id', $cell['slot_id'])
                ->get();

            foreach ($slots as $slot) {
                $slot->delete();
                $cleared++;
            }

            if (!$closeout->closeout_day_time_slots()->exists()) {
                $closeout->delete();
            }
        }

        if ($restoreClosedCell) {
            $cleared += $this->createSchedulerCloseout($inventory, $cell);
        }

        $this->cleanupUnusedSchedulerPreserveRows($inventory, $date);

        return [
            'cleared' => $cleared,
            'skipped' => $cleared === 0 && $manualSkipped === 0 ? 1 : 0,
            'manual_skipped' => $manualSkipped,
        ];
    }

    private function hasManualScheduleRecordForCell(Inventory $inventory, array $cell): bool
    {
        $date = $cell['date'];
        $dayId = (int) Carbon::parse($date)->dayOfWeekIso;

        $manualRangeExists = InventorySlotRange::where('inventory_id', $inventory->id)
            ->where('time_slot_id', $cell['slot_id'])
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function ($query) {
                $query->whereNull('note')
                    ->orWhere('note', 'not like', '[Scheduler]%');
            })
            ->exists();

        if ($manualRangeExists) {
            return true;
        }

        return Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where(function ($query) {
                $query->where(function ($nameQuery) {
                    $nameQuery->whereNull('name')
                        ->orWhere('name', 'not like', '[Scheduler]%');
                })->where(function ($reasonQuery) {
                    $reasonQuery->whereNull('reason')
                        ->orWhere('reason', 'not like', '[Scheduler]%');
                });
            })
            ->where(function ($query) use ($dayId, $cell) {
                $query->whereDoesntHave('closeout_day_time_slots')
                    ->orWhereHas('closeout_day_time_slots', function ($slotQuery) use ($dayId, $cell) {
                        $slotQuery->where('day_id', $dayId)
                            ->where('time_slot_id', $cell['slot_id']);
                    });
            })
            ->exists();
    }

    private function isBaseScheduledCell(Inventory $inventory, array $cell): bool
    {
        $dayId = (int) Carbon::parse($cell['date'])->dayOfWeekIso;
        $allowedDays = $inventory->inventory_type_days->pluck('type_day_id')->map(fn($id) => (int) $id)->all();
        if (!in_array($dayId, $allowedDays, true)) {
            return false;
        }

        return $inventory->inventory_time_slots
            ->where('id', $cell['slot_id'])
            ->filter(fn($slot) => !$slot->is_override_only)
            ->isNotEmpty();
    }

    private function cleanupUnusedSchedulerPreserveRows(Inventory $inventory, string $date): void
    {
        $hasActiveSchedulerOverride = InventorySlotRange::where('inventory_id', $inventory->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('note', 'like', '[Scheduler]%')
            ->where('note', 'not like', '[Scheduler] Preserve base schedule%')
            ->exists();

        if ($hasActiveSchedulerOverride) {
            return;
        }

        InventorySlotRange::where('inventory_id', $inventory->id)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('note', 'like', '[Scheduler] Preserve base schedule%')
            ->delete();
    }
}
