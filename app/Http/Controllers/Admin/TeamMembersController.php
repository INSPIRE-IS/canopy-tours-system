<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class TeamMembersController extends Controller
{
    public function index()
    {
        $team  = Team::where('owner_id', auth()->user()->id)->first();
        $roles = Role::get(['id', 'name']);
        $isAdmin = auth()->user()->hasAnyRoles(['Admin']);
        $users = User::where(function ($query) use ($team, $isAdmin) {
            $query->where('team_id', $team->id);
            if ($isAdmin) {
                $query->orWhereJsonContains('integration_allowed_team_ids', $team->id);
            }
        })->when(! $isAdmin, fn ($query) => $query->whereDoesntHave(
            'roles', fn ($roles) => $roles->where('name', 'Integration API')
        ))->get();

        return view('Admin.team-members.index', compact('team', 'users', 'roles'));
    }

    public function invite(Request $request)
    {
        $request->validate(['email' => 'email']);
        $team    = Team::where('owner_id', auth()->user()->id)->first();
        $url     = URL::signedRoute('register', ['team' => $team->id]);
        $message = new \App\Notifications\TeamMemberInvite($url);
        Notification::route('mail', $request->input('email'))->notify($message);

        return redirect()->back()->with('message', 'Invite sent.');
    }
}
