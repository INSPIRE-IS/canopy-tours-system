<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountPasswordLink;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
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
        abort_if(Gate::denies('users_access'), 403);
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $teamId = Session::get('team_id');

        $canManageIntegrationTokens = $user->hasAnyRoles(['Admin']);
        $roles = Role::with('permissions')->whereNotIn('name', ['admin', 'super'])
            ->when(! $canManageIntegrationTokens, fn ($query) => $query->where('name', '!=', 'Integration API'))
            ->select('name', 'id')->get();

        $teams = Team::select('name', 'id')->whereNotNull('name')->get();
        // dd($teams);
        // $users =  User::with('roles')->get();
        //dd($user->roles->pluck('name'));
        $relations = [
            'roles.permissions',
            'team',
        ];
        if ($canManageIntegrationTokens) {
            $relations['tokens'] = fn ($query) => $query
                ->where('name', 'like', 'everlytic-%')
                ->select([
                    'id',
                    'tokenable_type',
                    'tokenable_id',
                    'name',
                    'abilities',
                    'last_used_at',
                    'expires_at',
                    'created_at',
                ]);
        }

        $users = User::with($relations)
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->when(! $canManageIntegrationTokens, fn ($query) => $query->whereDoesntHave(
                'roles', fn ($roles) => $roles->where('name', 'Integration API')
            ))

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

        $users->getCollection()->transform(function (User $listedUser) use ($canManageIntegrationTokens) {
            $tokenMetadata = $canManageIntegrationTokens
                ? $listedUser->tokens->map(fn (PersonalAccessToken $token) => [
                        'id' => $token->id,
                        'name' => $token->name,
                        'created_at' => $token->created_at?->toIso8601String(),
                        'last_used_at' => $token->last_used_at?->toIso8601String(),
                        'expires_at' => $token->expires_at?->toIso8601String(),
                    ])->values()
                : collect();
            $listedUser->setAttribute('integration_tokens', $tokenMetadata);
            $listedUser->unsetRelation('tokens');

            return $listedUser;
        });

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
        $sendSetup = $request->boolean('send_password_setup');
        $attributes = $request->except('send_password_setup', 'integration_allowed_team_ids');
        if ($sendSetup) {
            $attributes['password'] = Str::random(64);
        }

        $user = DB::transaction(function () use ($attributes, $request) {
            $user = User::create($attributes);
            $user->roles()->sync($request->input('roles', []));
            if ($request->integrationTeamIds() !== null) {
                $user->forceFill([
                    'team_id' => null,
                    'integration_allowed_team_ids' => $request->integrationTeamIds(),
                ])->save();
            }

            return $user;
        });

        $redirect = redirect()->route('admin.users.index');
        if (! $sendSetup) {
            return $redirect;
        }

        return $this->sendPasswordSetup($request, $user, 'User created.');
    }

    private function sendPasswordSetup(Request $request, User $user, string $result, bool $invalidatePassword = false)
    {
        try {
            $status = Password::broker('account_setup')->sendResetLink(
                ['email' => $user->email],
                fn (User $recipient, string $token) => $recipient->notify(
                    new AccountPasswordLink($token, invitedBy: $request->user()->email)
                ),
            );
        } catch (\Exception $exception) {
            report($exception);
            $status = null;
        }

        if ($status === Password::RESET_LINK_SENT && $invalidatePassword) {
            DB::transaction(function () use ($user) {
                $current = User::withoutGlobalScope('team_id')->lockForUpdate()->find($user->id);
                // Do not overwrite a password set while the email was being sent.
                if (! $current || $current->email !== $user->email || $current->password !== $user->password) {
                    return;
                }

                $current->forceFill([
                    'password' => Str::random(64),
                    'remember_token' => Str::random(60),
                ])->save();
                Password::broker()->deleteToken($current);
            });
        }

        return $status === Password::RESET_LINK_SENT
            ? redirect()->route('admin.users.index')->with('success', "{$result} Password link sent.")
            : redirect()->route('admin.users.index')->with('error', "{$result} The password link could not be sent. Try again from Edit User or ask the user to use Forgot your password? on the login page.");
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $sendSetup = $request->boolean('send_password_setup');
        DB::transaction(function () use ($request, $user, $sendSetup) {
            $previousUser = clone $user;
            $attributes = $request->except('integration_allowed_team_ids', 'send_password_setup');
            if ($sendSetup) {
                unset($attributes['password']);
            }
            $user->fill($attributes);
            $ids = $request->integrationTeamIds();
            $user->forceFill(['integration_allowed_team_ids' => $ids]);
            if ($ids !== null) {
                $user->team_id = null;
            }
            $emailChanged = $user->isDirty('email');
            if ($emailChanged || $user->isDirty('password')) {
                Password::broker()->deleteToken($previousUser);
                Password::broker('account_setup')->deleteToken($previousUser);
            }
            if ($emailChanged) {
                Password::broker()->deleteToken($user);
                Password::broker('account_setup')->deleteToken($user);
            }
            $user->save();
            $user->roles()->sync($request->input('roles', []));
        });

        return $sendSetup
            ? $this->sendPasswordSetup($request, $user, 'User updated.', invalidatePassword: true)
            : redirect()->route('admin.users.index');
    }

    public function destroy(User $user)
    {
        abort_if(Gate::denies('users_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if($user->hasAnyRoles(['Integration API']) && ! auth()->user()->hasAnyRoles(['Admin']), 403);

        $user->tokens()->delete();
        $user->delete();

        return back();
    }

    public function destroyIntegrationToken(
        Request $request,
        User $user,
        PersonalAccessToken $token,
    ) {
        abort_unless($request->user()?->hasAnyRoles(['Admin']), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'password' => ['required', 'current_password:web'],
        ]);

        $belongsToUser = $token->tokenable_type === User::class
            && (int) $token->tokenable_id === (int) $user->id;
        if (! $belongsToUser || ! str_starts_with($token->name, 'everlytic-')) {
            throw ValidationException::withMessages([
                'password' => 'The selected Everlytic token does not belong to this user.',
            ]);
        }

        $token->delete();

        return response()->json([
            'message' => 'Bearer token revoked. The integration user can create a replacement after logging in.',
        ])->header('Cache-Control', 'private, no-store');
    }
}
