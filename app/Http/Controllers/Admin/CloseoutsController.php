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
use Symfony\Component\HttpFoundation\Response;

class CloseoutsController extends Controller
{


    public function index()
    {
        abort_if(Gate::denies('closeout_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $closeouts = Closeout::with(['service', 'season', 'updated_by', 'team'])->get();

        return view('Admin.closeouts.index', compact('closeouts'));
    }

    public function create()
    {
        abort_if(Gate::denies('closeout_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('Admin.closeouts.create', compact('seasons', 'services', 'updated_bies'));
    }

    public function store(StoreCloseoutRequest $request)
    {
        $closeout = Closeout::create($request->all());

        return redirect()->route('Admin.closeouts.index');
    }

    public function edit(Closeout $closeout)
    {
        abort_if(Gate::denies('closeout_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $closeout->load('service', 'season', 'updated_by', 'team');

        return view('Admin.closeouts.edit', compact('closeout', 'seasons', 'services', 'updated_bies'));
    }

    public function update(UpdateCloseoutRequest $request, Closeout $closeout)
    {
        $closeout->update($request->all());

        return redirect()->route('Admin.closeouts.index');
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
