<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyTeamRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\File;
use App\Models\LookupItem;
use App\Models\Team;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        abort_if(Gate::denies('teams_access'), 403);
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $teamId = Session::get('team_id');
        $teams = Team::with('owner', 'file_image', 'file_logo') // with(['owner'])
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
        //dd('here');
        //dd($request->all());
        // TODO: add logic to save image and logo
        $data             = $request->all();

        $data['owner_id'] = auth()->user()->id;
        $team = null;
        DB::transaction(function () use ($data, &$team) {
            $team = Team::create($data);
            LookupItem::ensureSettingsForTeam($team->id);
        });

        if ($request->hasFile('logo')) {
            $uploaded = $request->file('logo');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 1,
                'path' => $path,
            ]);
        }

        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 2,
                'path' => $path,
            ]);
        }

        return response()->json([
            'success' => true,
            'error' => 'none',
        ], 200);
    }

    public function update(UpdateTeamRequest $request, Team $team)
    {
        // dd($team->wasChanged());
        // $team->forceFill([
        //     'name' => 'FORCE TEST ' . now(),
        // ])->save();

        // dd($team->wasChanged());
        // dd(
        //     $request->all(),
        //     $request->validated(),
        //     $team->getDirty()
        // );
        $data = $request->all();
        $data['owner_id'] = auth()->id();
        //dd($data);
        $team->update($data);

        if ($request->hasFile('logo')) {
            $uploaded = $request->file('logo');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            optional(
                File::where('table_name', 'teams')
                    ->where('table_row_id', $team->id)
                    ->where('type_file_id', 1)
                    ->first()
            )?->delete();

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 1,
                'path' => $path,
            ]);
        }

        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $path = $uploaded->store("teams/{$team->id}", 'public');

            optional(
                File::where('table_name', 'teams')
                    ->where('table_row_id', $team->id)
                    ->where('type_file_id', 2)
                    ->first()
            )?->delete();

            File::create([
                'team_id' => auth()->user()->team_id,
                'table_name' => 'teams',
                'table_row_id' => $team->id,
                'file_name' => $uploaded->getClientOriginalName(),
                'file_type' => $uploaded->getMimeType(),
                'type_file_id' => 2,
                'path' => $path,
            ]);
        }
        return response()->json([
            'success' => true,
            'error' => 'none',
        ], 200);
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


    public function change(Request $request)
    {

        $request->validate([
            'team_id' => 'nullable|exists:teams,id',
        ]);
        //dd($request);
        $team_id = $request->team_id;

        //$team = Team::find($team_id);$team &&
        $user = auth()->user();
        $user = User::find($user->id)->with('roles')->first();
        // dd($user);
        if ($user->hasAnyRoles(['Admin', 'Director'])) {
            $user->team_id = $team_id;
            $user->save();
            session(['team_id' => $user->team_id]);

            return redirect()->back()->with('message', 'Team changed.');
            Log::info('site controller site id: ' . $team->id);
        } else {
            Log::info('site controller site: none');
        }

        return redirect()->back()->with('message', 'Site changed.');

        // $team = Team::find($team_id);
        // //dd($team);
        // if ($team) {
        //     session(['team_id' => $team->id]);
        //     Log::info('site controller site id: ' . $team->id);
        // } else {
        //     Log::info('site controller site: none');
        // }
        // return redirect()->back()->with('message', 'Site changed.');
    }
}
