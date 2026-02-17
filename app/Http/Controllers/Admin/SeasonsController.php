<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Models\Season;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use App\Models\File;
use App\Models\SeasonField;
use App\Models\SeasonFieldValue;
use App\Models\Service;
use App\Services\QuestionnaireSeeder;
use Illuminate\Support\Facades\Validator;

class SeasonsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('seasons_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'service_id');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $seasons = Season::with([
            'service',
            'created_by',
            'updated_by',
            'team',
        ])
            ->where('service_id', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);


        $services = Service::select('name', 'id')->get();

        return Inertia::render('Admin/Seasons/Index1', [
            'seasons' => $seasons,
            'services' => $services,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'fieldValues' => null,
        ]);
    }

    public function store(StoreSeasonRequest $request)
    {
        //dd($request->all());
        $season = Season::create($request->all());
        app(QuestionnaireSeeder::class)->seedForSeason($season);
        //return redirect()->back()->with('success', 'Season created successfully');

        return redirect()->route('admin.seasons.index');
    }

    public function update(UpdateSeasonRequest $request, Season $season)
    {
        ///dd('here');

        $season->update($request->all());

        return redirect()->route('admin.seasons.index');
    }

    public function show(Season $season)
    {
        abort_if(Gate::denies('season_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->load('created_by', 'updated_by', 'team');

        return view('Admin.seasons.show', compact('season'));
    }

    public function destroy(Season $season)
    {
        abort_if(Gate::denies('season_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->delete();

        return back();
    }
}
