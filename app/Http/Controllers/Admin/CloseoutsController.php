<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCloseoutRequest;
use App\Http\Requests\UpdateCloseoutRequest;
use App\Models\Closeout;
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
        $closeouts = Closeout::with(['service', 'season.inventory', 'updated_by', 'team'])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

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
        $closeout = Closeout::create($request->all());

        return redirect()->route('admin.closeouts.index');
    }


    public function update(UpdateCloseoutRequest $request, Closeout $closeout)
    {
        $closeout->update($request->all());

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
