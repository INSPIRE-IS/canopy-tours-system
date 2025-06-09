<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_if(Gate::denies('user_access'), 403);
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $teamId = Session::get('team_id');

        $roles = Role::whereNotIn('name', ['admin', 'super'])->select('name', 'id')->get();
        $teams = Team::select('name', 'id')->whereNotNull('name')->get();
        // dd($teams);
        // $users =  User::with('roles')->get();
        //dd($user->roles->pluck('name'));
        $users = User::with(['roles.permissions', 'team'])
            ->where('name', 'like', '%' . $searchTerm . '%')

            // unless user has this role do not show users with this role
            ->when(
                ! $user->hasAnyRoles(['Admin', 'Director']),
                function ($query) {
                    $query->whereDoesntHave('roles', function ($subQuery) {
                        $subQuery->whereIn('name', ['Admin', 'Director']);
                    });
                }
            )
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'teams' => $teams ?? [],
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'can' => [
                'user_show' => $user->can('user_show'),
                'user_create' => $user->can('user_create'),
                'user_edit' => $user->can('user_edit'),
                'user_delete' => $user->can('user_delete'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        // dd($request->all());
        $user = User::create($request->all());
        $user->roles()->sync($request->input('roles', []));

        // return redirect()->route('Admin.users.index');
        // $teams = $request->input('teams', []);
        // $roles = $request->input('roles', []);
        // $user_data = $request->all();
        // $user_data['password'] = bcrypt($request->input('password'));
        // $user = User::create($user_data);
        // $user->teams()->sync($request->input('teams', []));
        // foreach ($teams as $teamId) {
        //     $user->roles()->attach($roles, ['team_id' => $teamId]);
        // }

        // foreach ($roles as $roleId) {
        //     foreach ($teams as $teamId) {
        //         $user->roles()->attach($roleId, ['team_id' => $teamId]);
        //     }
        // }
        //$user->roles()->sync($request->input('roles', []));
        return redirect()->route('users.index');
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->all());
        $user->roles()->sync($request->input('roles', []));

        return redirect()->route('users.index');
    }

    public function destroy(User $user)
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->delete();

        return back();
    }
}
