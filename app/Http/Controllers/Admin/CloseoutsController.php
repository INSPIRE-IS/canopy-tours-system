<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCloseoutRequest;
use App\Http\Requests\UpdateCloseoutRequest;
use App\Models\Closeout;
use App\Models\CloseoutDayTimeSlot;
use App\Models\InventoryTimeSlot;
use App\Models\Season;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CloseoutsController extends Controller
{


    public function index(Request $request)
    {
        abort_if(Gate::denies('closeouts_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //$closeouts = Closeout::with(['service', 'season', 'updated_by', 'team'])->get();

        //return view('Admin.closeouts.index', compact('closeouts'));

        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $allowedSorts = ['id', 'name', 'service_id', 'season_id', 'start_date', 'end_date', 'priority'];
        if (!in_array($sortColumn, $allowedSorts, true)) {
            $sortColumn = 'name';
        }
        $sortDirection = $sortDirection === 'desc' ? 'desc' : 'asc';
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $baseCloseoutsQuery = Closeout::with(['service', 'season.inventory', 'closeout_day_time_slots.time_slot'])
            ->where('name', 'like', '%' . $searchTerm . '%');

        if ($request->boolean('check_overlaps')) {
            $baseCloseoutsQuery
                ->orderBy('service_id')
                ->orderBy('season_id')
                ->orderBy('start_date')
                ->orderBy('end_date');
        } else {
            $baseCloseoutsQuery->orderBy($sortColumn, $sortDirection);
        }

        $closeouts = (clone $baseCloseoutsQuery)
            ->paginate(10);

        $overlapGroups = $this->buildOverlapGroups(
            Closeout::with(['service', 'season', 'closeout_day_time_slots.time_slot'])->get()
        );

        $overlapIds = collect($overlapGroups)
            ->flatMap(fn ($group) => $group['closeout_ids'])
            ->unique()
            ->values();

        foreach ($closeouts->items() as $closeout) {
            $closeout->setAttribute('has_overlap', $overlapIds->contains($closeout->id));
            $closeout->setAttribute('overlap_groups', collect($overlapGroups)
                ->filter(fn ($group) => in_array($closeout->id, $group['closeout_ids'], true))
                ->values()
                ->all());
        }
        //dd($closeouts);
        $services = Service::with('seasons', 'seasons.inventory', 'seasons.inventory.inventory_type_days.type_day', 'seasons.inventory.inventory_time_slots')->select('id', 'name')->get();
        //dd($services[0]->seasons[0]->inventory->inventory_time_slots);
        //dd($services[0]->seasons[0]->inventory->inventory_type_days);
        //$seasons = Season::select('id', 'name')->get();


        return Inertia::render('Admin/Closeouts/Index', [
            'closeouts' => $closeouts,
            'services' => $services,
            //'seasons' => $seasons,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'fieldValues' => null,
            'overlapGroups' => $overlapGroups,
            'checkOverlaps' => $request->boolean('check_overlaps'),
        ]);
    }

    public function store(StoreCloseoutRequest $request)
    {
        $this->normalizeCloseoutCellsInRequest($request);

        //dd($request->all());
        //$closeout = Closeout::create($request->all());
        $closeoutData = $request->only([
            'service_id',
            'season_id',
            'name',
            'start_date',
            'end_date',
            'reason',
            'priority',
            'team_id',
            'created_by_id',
            'updated_by_id'
        ]);
        $closeoutData['priority'] = $closeoutData['priority'] ?? 50;
        $closeout = Closeout::create($closeoutData);

        foreach ($request->input('day_id', []) as $index => $dayId) {
            logger()->info('dayId ' . $dayId);
            logger()->info('time_slot_id ' . $request->input('time_slot_id', [])[$index]);
            $res = CloseoutDayTimeSlot::create([
                'closeout_id' => $closeout->id,
                'day_id' => $dayId,
                'time_slot_id' => $request->input('time_slot_id', [])[$index],
                'team_id' => $closeout->team_id,
            ]);
            logger()->info('res ' . $res);
        }



        return redirect()->route('admin.closeouts.index');
    }


    public function update(UpdateCloseoutRequest $request, Closeout $closeout)
    {
        $this->normalizeCloseoutCellsInRequest($request);

        $closeoutData = $request->only([
            'service_id',
            'season_id',
            'name',
            'start_date',
            'end_date',
            'reason',
            'priority',
            'team_id',
            'created_by_id',
            'updated_by_id'
        ]);
        $closeoutData['priority'] = $closeoutData['priority'] ?? 50;
        $closeout->update($closeoutData);
        $closeout_day_time_slots = CloseoutDayTimeSlot::where('closeout_id', $closeout->id)->get();
        foreach ($closeout_day_time_slots as $closeout_day_time_slot) {
            $closeout_day_time_slot->delete();
        }
        foreach ($request->input('day_id', []) as $index => $dayId) {
            CloseoutDayTimeSlot::create([
                'closeout_id' => $closeout->id,
                'day_id' => $dayId,
                'time_slot_id' => $request->input('time_slot_id', [])[$index],
                'team_id' => $closeout->team_id,
            ]);
        }

        return redirect()->route('admin.closeouts.index');
    }

    public function show(Closeout $closeout)
    {
        abort_if(Gate::denies('closeout_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $closeout->load('service', 'season', 'updated_by', 'team');

        return view('Admin.closeouts.show', compact('closeout'));
    }

    public function destroy(Closeout $closeout)
    {
        abort_if(Gate::denies('closeouts_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $closeout->closeout_day_time_slots()->delete();
        $closeout->delete();

        return back();
    }

    private function normalizeCloseoutCellsInRequest(Request $request): void
    {
        $dayIds = $request->input('day_id', []);
        $timeSlotIds = $request->input('time_slot_id', []);

        if (!is_array($dayIds) || !is_array($timeSlotIds) || count($dayIds) !== count($timeSlotIds)) {
            throw ValidationException::withMessages([
                'day_id' => 'Closeout day/time selections are invalid. Please reselect the days and times.',
            ]);
        }

        $requestedCells = collect($this->cellsFromRequest($dayIds, $timeSlotIds))->values();

        if ($requestedCells->isEmpty()) {
            throw ValidationException::withMessages([
                'day_id' => 'No slots are selected to close. If you want to reopen this whole period, use Remove on the closeout table instead of saving this closeout.',
            ]);
        }

        $request->merge([
            'day_id' => $requestedCells->pluck('day_id')->all(),
            'time_slot_id' => $requestedCells->pluck('time_slot_id')->all(),
        ]);
    }

    private function buildOverlapGroups($closeouts): array
    {
        $payloads = $closeouts
            ->map(fn (Closeout $closeout) => $this->closeoutOverlapPayload($closeout))
            ->values();

        $groups = [];

        for ($i = 0; $i < $payloads->count(); $i++) {
            for ($j = $i + 1; $j < $payloads->count(); $j++) {
                $conflict = $this->overlapConflictFor($payloads[$i], $payloads[$j]);
                if ($conflict) {
                    $groups[] = [
                        'closeout_ids' => [$payloads[$i]['id'], $payloads[$j]['id']],
                        'service_id' => $payloads[$i]['service_id'],
                        'service_name' => $payloads[$i]['service_name'],
                        'season_id' => $payloads[$i]['season_id'],
                        'season_name' => $payloads[$i]['season_name'],
                        'date_window' => $conflict['date_window'],
                        'cells' => $conflict['cells'],
                        'names' => [$payloads[$i]['name'], $payloads[$j]['name']],
                    ];
                }
            }
        }

        return $groups;
    }

    private function overlapConflictFor(array $left, array $right): ?array
    {
        if ($left['service_id'] !== $right['service_id'] || $left['season_id'] !== $right['season_id']) {
            return null;
        }

        if ($left['end_date'] < $right['start_date'] || $right['end_date'] < $left['start_date']) {
            return null;
        }

        $overlapCells = collect($left['cells'])
            ->filter(fn ($leftCell) => collect($right['cells'])->contains(
                fn ($rightCell) => $this->closeoutCellsOverlap($leftCell, $rightCell)
            ))
            ->map(fn ($cell) => $cell['label'])
            ->unique()
            ->values()
            ->all();

        if (empty($overlapCells)) {
            return null;
        }

        return [
            'other_id' => $right['id'],
            'other_name' => $right['name'],
            'date_window' => [
                'start' => max($left['start_date'], $right['start_date']),
                'end' => min($left['end_date'], $right['end_date']),
            ],
            'cells' => $overlapCells,
        ];
    }

    private function closeoutCellsOverlap(array $leftCell, array $rightCell): bool
    {
        $sameDay = $leftCell['day_id'] === '*' || $rightCell['day_id'] === '*' || $leftCell['day_id'] === $rightCell['day_id'];
        $sameTime = $leftCell['time_key'] === '*' || $rightCell['time_key'] === '*' || $leftCell['time_key'] === $rightCell['time_key'];

        return $sameDay && $sameTime;
    }

    private function closeoutOverlapPayload(Closeout $closeout): array
    {
        return [
            'id' => $closeout->id,
            'name' => $closeout->name,
            'service_id' => (int) $closeout->service_id,
            'service_name' => $closeout->service?->name,
            'season_id' => (int) $closeout->season_id,
            'season_name' => $closeout->season?->name,
            'start_date' => $this->normalDate($closeout->getRawOriginal('start_date') ?: $closeout->start_date),
            'end_date' => $this->normalDate($closeout->getRawOriginal('end_date') ?: $closeout->end_date),
            'priority' => (int) ($closeout->priority ?? 50),
            'cells' => $this->cellsFromCloseout($closeout),
        ];
    }

    private function cellsFromCloseout(Closeout $closeout): array
    {
        if ($closeout->closeout_day_time_slots->isEmpty()) {
            return [[
                'day_id' => '*',
                'time_key' => '*',
                'label' => 'All days / all times',
            ]];
        }

        return $closeout->closeout_day_time_slots
            ->map(function (CloseoutDayTimeSlot $slot) {
                $time = $slot->time_slot?->start_time ? substr($slot->time_slot->start_time, 0, 5) : (string) $slot->time_slot_id;

                return [
                    'day_id' => (int) $slot->day_id,
                    'time_key' => $time,
                    'label' => "Day {$slot->day_id} {$time}",
                ];
            })
            ->unique(fn ($cell) => $cell['day_id'] . '|' . $cell['time_key'])
            ->values()
            ->all();
    }

    private function cellsFromRequest(array $dayIds, array $timeSlotIds): array
    {
        $slotTimes = InventoryTimeSlot::withTrashed()
            ->whereIn('id', $timeSlotIds)
            ->get()
            ->mapWithKeys(fn (InventoryTimeSlot $slot) => [$slot->id => substr((string) $slot->start_time, 0, 5)]);

        return collect($dayIds)
            ->map(function ($dayId, $index) use ($timeSlotIds, $slotTimes) {
                $timeSlotId = $timeSlotIds[$index] ?? null;
                $timeKey = $slotTimes[$timeSlotId] ?? (string) $timeSlotId;

                return [
                    'day_id' => (int) $dayId,
                    'time_slot_id' => (int) $timeSlotId,
                    'time_key' => $timeKey,
                    'label' => "Day {$dayId} {$timeKey}",
                ];
            })
            ->unique(fn ($cell) => $cell['day_id'] . '|' . $cell['time_key'])
            ->values()
            ->all();
    }

    private function normalDate($date): string
    {
        return Carbon::parse($date)->toDateString();
    }
}
