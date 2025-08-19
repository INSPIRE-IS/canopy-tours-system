<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCloseoutRequest;
use App\Http\Requests\UpdateCloseoutRequest;
use App\Models\Closeout;
use App\Models\CloseoutDayTimeSlot;
use App\Models\Season;
use App\Models\Service;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;
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
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $closeouts = Closeout::with(['service', 'season.inventory', 'closeout_day_time_slots'])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
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
        ]);
    }

    public function store(StoreCloseoutRequest $request)
    {
        //dd($request->all());
        //$closeout = Closeout::create($request->all());
        $closeout = Closeout::create($request->only([
            'service_id',
            'season_id',
            'name',
            'start_date',
            'end_date',
            'reason',
            'team_id',
            'created_by_id',
            'updated_by_id'
        ]));

        foreach ($request->day_id as $index => $dayId) {
            logger()->info('dayId ' . $dayId);
            logger()->info('time_slot_id ' . $request->time_slot_id[$index]);
            $res = CloseoutDayTimeSlot::create([
                'closeout_id' => $closeout->id,
                'day_id' => $dayId,
                'time_slot_id' => $request->time_slot_id[$index],
                'team_id' => $closeout->team_id,
            ]);
            logger()->info('res ' . $res);
        }



        return redirect()->route('admin.closeouts.index');
    }


    public function update(UpdateCloseoutRequest $request, Closeout $closeout)
    {
        $closeout->update($request->only([
            'service_id',
            'season_id',
            'name',
            'start_date',
            'end_date',
            'reason',
            'team_id',
            'created_by_id',
            'updated_by_id'
        ]));
        $closeout_day_time_slots = CloseoutDayTimeSlot::where('closeout_id', $closeout->id)->get();
        foreach ($closeout_day_time_slots as $closeout_day_time_slot) {
            $closeout_day_time_slot->delete();
        }
        foreach ($request->day_id as $index => $dayId) {
            CloseoutDayTimeSlot::create([
                'closeout_id' => $closeout->id,
                'day_id' => $dayId,
                'time_slot_id' => $request->time_slot_id[$index],
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
        abort_if(Gate::denies('closeout_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $closeout->delete();

        return back();
    }
}
