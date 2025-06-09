<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTeamRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;


class TeamController extends Controller
{
    public function index(Request $request)
    {
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(Gate::denies('team_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $teams = Team::with(['owner'])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);


        return Inertia::render('Admin/Teams/Index', [
            'teams' => $teams,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }


    public function store(StoreTeamRequest $request)
    {
        $data             = $request->all();
        $data['owner_id'] = auth()->user()->id;
        $team             = Team::create($data);

        return redirect()->route('teams.index');
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        $team->update($request->all());

        return redirect()->route('teams.index');
    }

    public function show(Team $team)
    {
        abort_if(Gate::denies('team_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $team->load('owner');

        return view('Admin.teams.show', compact('team'));
    }

    public function destroy(Team $team)
    {
        abort_if(Gate::denies('team_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $team->delete();

        return back();
    }


    public function change($team_id)
    {
        ///////// update this J if the user is director then we update the team id on user to the chosen team 
        ///////////// otherwise we update the team id on user to null then they see all the data

        //$request->validate(['site_id' => 'required']);
        //$teamId = Session::get('team_id'); 
        //dd($site_id);

        $team = Team::find($team_id);
        //dd($team);
        if ($team) {
            session(['team_id' => $team->id]);
            Log::info('site controller site id: ' . $team->id);
        } else {
            Log::info('site controller site: none');
        }
        return redirect()->back()->with('message', 'Site changed.');
    }
}
