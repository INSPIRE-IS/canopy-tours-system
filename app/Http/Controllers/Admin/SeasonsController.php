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
use Symfony\Component\HttpFoundation\Response;

class SeasonsController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('season_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $seasons = Season::with(['service', 'updated_by', 'team'])->get();

        return view('Admin.seasons.index', compact('seasons'));
    }

    public function create()
    {
        abort_if(Gate::denies('season_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('Admin.seasons.create', compact('services', 'updated_bies'));
    }

    public function store(StoreSeasonRequest $request)
    {
        $season = Season::create($request->all());

        return redirect()->route('Admin.seasons.index');
    }

    public function edit(Season $season)
    {
        abort_if(Gate::denies('season_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $season->load('service', 'updated_by', 'team');

        return view('Admin.seasons.edit', compact('season', 'services', 'updated_bies'));
    }

    public function update(UpdateSeasonRequest $request, Season $season)
    {
        $season->update($request->all());

        return redirect()->route('Admin.seasons.index');
    }

    public function show(Season $season)
    {
        abort_if(Gate::denies('season_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->load('service', 'updated_by', 'team');

        return view('Admin.seasons.show', compact('season'));
    }

    public function destroy(Season $season)
    {
        abort_if(Gate::denies('season_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $season->delete();

        return back();
    }
}
