<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroySeasonRequest;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Models\Season;
use App\Models\Service;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class SeasonsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('season_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $seasons = Season::with(['service', 'updated_by', 'team'])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        $services = Service::select('name', 'id')->get();
        // dd($services);
        return Inertia::render('Admin/Seasons/Index', [
            'seasons' => $seasons,
            'services' => $services,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(StoreSeasonRequest $request)
    {
        //dd($request->all());
        $season = Season::create($request->all());

        return redirect()->route('seasons.index');
    }

    public function update(UpdateSeasonRequest $request, Season $season)
    {
        $season->update($request->all());

        return redirect()->route('seasons.index');
    }

    public function show(Season $season)
    {
        abort_if(Gate::denies('season_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->load('service', 'updated_by', 'team');

        return view('seasons.show', compact('season'));
    }

    public function destroy(Season $season)
    {
        abort_if(Gate::denies('season_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->delete();

        return back();
    }
}
