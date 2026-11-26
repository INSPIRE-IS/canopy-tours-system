<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountPasswordLink;
use App\Services\AttendedGuestFeedService;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;

function createEverlyticIntegrationUser(Team $team, string $email = 'integration.user@example.test'): User
{
    $user = User::withoutEvents(fn () => User::query()->create([
        'name' => 'Integration',
        'surname' => 'User',
        'email' => $email,
        'password' => bcrypt('test-password'),
        'team_id' => $team->id,
    ]));
    $user->roles()->attach(Role::query()->where('name', 'Integration API')->firstOrFail());
    $user->forceFill(['team_id' => null, 'integration_allowed_team_ids' => [$team->id]])->save();

    return $user;
}

function createEverlyticAdmin(Team $team, string $email = 'integration.admin@example.test'): User
{
    $user = User::withoutEvents(fn () => User::query()->create([
        'name' => 'Integration',
        'surname' => 'Admin',
        'email' => $email,
        'password' => bcrypt('admin-password'),
        'team_id' => $team->id,
    ]));
    $role = Role::query()->firstOrCreate(['name' => 'Admin']);
    $permissions = collect(['users_access', 'users_create', 'users_edit', 'users_delete'])
        ->map(fn (string $name) => Permission::query()->firstOrCreate(['name' => $name])->id);
    $role->permissions()->syncWithoutDetaching($permissions);
    $user->roles()->attach($role);

    return $user;
}

it('creates a restricted Integration API role with only documentation access', function () {
    $role = Role::query()->where('name', 'Integration API')->firstOrFail();

    expect($role->permissions()->pluck('name')->all())
        ->toBe(['integration_api_docs_access']);
});

it('backfills a legacy integration site without overwriting a configured allowlist', function () {
    $site = Team::query()->create(['name' => 'Legacy Integration Site']);
    $other = Team::query()->create(['name' => 'Configured Integration Site']);
    $legacy = createEverlyticIntegrationUser($site);
    $legacy->forceFill(['team_id' => $site->id, 'integration_allowed_team_ids' => null])->save();
    $configured = createEverlyticIntegrationUser($other, 'configured@example.test');
    $configured->forceFill(['team_id' => $site->id])->save();
    $staff = createEverlyticAdmin($site);

    $migration = require database_path('migrations/2026_11_26_000001_add_integration_allowed_team_ids_to_users.php');
    $migration->up();

    expect($legacy->fresh()->integration_allowed_team_ids)->toBe([$site->id])
        ->and($configured->fresh()->integration_allowed_team_ids)->toBe([$other->id])
        ->and($staff->fresh()->integration_allowed_team_ids)->toBeNull();
});

it('allows the Integration API user to view docs but redirects normal application routes', function () {
    $team = Team::query()->create(['name' => 'Karkloof API Test']);
    $user = createEverlyticIntegrationUser($team);

    $this->actingAs($user)
        ->get(route('integration.everlytic.docs'))
        ->assertOk()
        ->assertSee('Everlytic API documentation')
        ->assertSee('Karkloof API Test');

    $this->actingAs($user)
        ->get('/admin/dashboard')
        ->assertRedirect(route('integration.everlytic.docs'));
});

it('blocks ordinary users from the integration documentation', function () {
    $team = Team::query()->create(['name' => 'Ordinary User Site']);
    $user = User::withoutEvents(fn () => User::query()->create([
        'name' => 'Ordinary',
        'surname' => 'User',
        'email' => 'ordinary-docs-user@example.test',
        'password' => bcrypt('test-password'),
        'team_id' => $team->id,
    ]));

    $this->actingAs($user)
        ->get(route('integration.everlytic.docs'))
        ->assertForbidden();
});

it('redirects an Integration API login directly to its documentation', function () {
    $team = Team::query()->create(['name' => 'Login Redirect Site']);
    createEverlyticIntegrationUser($team, 'integration-login@example.test');

    $this->post(route('login'), [
        'email' => 'integration-login@example.test',
        'password' => 'test-password',
    ])->assertRedirect(route('integration.everlytic.docs'));
});

it('allows the restricted user to create one bearer token after confirming its password', function () {
    $team = Team::query()->create(['name' => 'Self Service Token Site']);
    $user = createEverlyticIntegrationUser($team, 'self-service@example.test');

    $this->actingAs($user)
        ->postJson(route('integration.everlytic.token.store'), ['password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $response = $this->actingAs($user)
        ->postJson(route('integration.everlytic.token.store'), ['password' => 'test-password'])
        ->assertCreated()
        ->assertHeader('cache-control', 'no-store, private')
        ->assertJsonPath('metadata.last_used_at', null)
        ->assertJsonPath('metadata.expires_at', null);

    expect($response->json('token'))->toContain('|')
        ->and(PersonalAccessToken::query()->where('tokenable_id', $user->id)->count())->toBe(1);

    $this->actingAs($user)
        ->postJson(route('integration.everlytic.token.store'), ['password' => 'test-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('allows only an admin with a confirmed password to revoke an integration token', function () {
    $team = Team::query()->create(['name' => 'Admin Revoke Site']);
    $integrationUser = createEverlyticIntegrationUser($team, 'revoke-target@example.test');
    $admin = createEverlyticAdmin($team);
    $token = $integrationUser->createToken('everlytic-test', [AttendedGuestFeedService::TOKEN_ABILITY])->accessToken;
    $route = route('admin.users.integration-tokens.destroy', [$integrationUser, $token]);

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($admin)
        ->deleteJson($route, ['password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($admin)
        ->deleteJson($route, ['password' => 'admin-password'])
        ->assertOk()
        ->assertJsonPath('message', 'Bearer token revoked. The integration user can create a replacement after logging in.');

    expect(PersonalAccessToken::query()->find($token->id))->toBeNull();
});

it('revokes all bearer tokens when an admin deletes an integration user', function () {
    $team = Team::query()->create(['name' => 'Delete Integration User Site']);
    $integrationUser = createEverlyticIntegrationUser($team, 'delete-target@example.test');
    $admin = createEverlyticAdmin($team, 'delete-admin@example.test');
    $integrationUser->createToken('everlytic-delete-test', [AttendedGuestFeedService::TOKEN_ABILITY]);

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($admin)
        ->delete(route('admin.users.destroy', $integrationUser))
        ->assertRedirect();

    expect(PersonalAccessToken::query()->where('tokenable_id', $integrationUser->id)->count())->toBe(0)
        ->and(User::withTrashed()->findOrFail($integrationUser->id)->deleted_at)->not->toBeNull();
});

it('requires an integration user to have one role and an assigned site', function () {
    $team = Team::query()->create(['name' => 'Integration Validation Site']);
    $admin = createEverlyticAdmin($team, 'validation-admin@example.test');
    $integrationRole = Role::query()->where('name', 'Integration API')->firstOrFail();
    $otherRole = Role::query()->firstOrCreate(['name' => 'Manager']);

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Invalid',
            'surname' => 'Integration',
            'email' => 'invalid-integration@example.test',
            'password' => 'test-password',
            'team_id' => $team->id,
            'roles' => [$integrationRole->id, $otherRole->id],
        ])
        ->assertSessionHasErrors('roles');

    $this->withoutMiddleware(EnsureEmailIsVerified::class)
        ->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Missing Site',
            'surname' => 'Integration',
            'email' => 'missing-site@example.test',
            'password' => 'test-password',
            'roles' => [$integrationRole->id],
        ])
        ->assertSessionHasErrors('integration_allowed_team_ids');
});

it('allows only admins to manage the integration account and validates comma-separated site IDs', function () {
    $site = Team::query()->create(['name' => 'First Allowed Site']);
    $second = Team::query()->create(['name' => 'Second Allowed Site']);
    $admin = createEverlyticAdmin($site);
    $integration = createEverlyticIntegrationUser($site);
    $role = Role::query()->where('name', 'Integration API')->firstOrFail();
    $payload = [
        'name' => 'Integration User',
        'email' => $integration->email,
        'roles' => [$role->id],
        'integration_allowed_team_ids' => "{$site->id}, {$second->id}, {$site->id}",
    ];

    $url = route('admin.users.update', $integration);
    $this->actingAs($admin)->put($url, array_replace($payload, [
        'integration_allowed_team_ids' => "{$site->id}, 999999",
    ]))->assertSessionHasErrors('integration_allowed_team_ids');
    $this->put($url, array_replace($payload, [
        'integration_allowed_team_ids' => "{$site->id},oops",
    ]))->assertSessionHasErrors('integration_allowed_team_ids');
    $this->put($url, $payload)->assertRedirect(route('admin.users.index'));
    expect($integration->refresh()->integration_allowed_team_ids)->toBe([$site->id, $second->id]);

    $staffRole = Role::query()->firstOrCreate(['name' => 'Site Manager']);
    $staffRole->permissions()->sync(Permission::query()
        ->whereIn('name', ['users_access', 'users_edit', 'users_delete'])->pluck('id'));
    $admin->roles()->sync([$staffRole->id]);
    $admin->unsetRelation('roles');

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
        ->assertDontSee($integration->email)->assertDontSee('Integration API');
    $this->put($url, array_replace($payload, ['roles' => [$staffRole->id]]))->assertForbidden();
    $this->delete(route('admin.users.destroy', $integration))->assertForbidden();
});

it('invites a new integration user to set their password with a single-use link', function () {
    Notification::fake();
    $team = Team::query()->create(['name' => 'Invite Site']);
    $admin = createEverlyticAdmin($team);
    $role = Role::query()->where('name', 'Integration API')->firstOrFail();
    config(['panel.registration_default_role' => $role->id]);
    $email = 'invite@example.test';

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Invited User',
        'surname' => 'Example',
        'email' => $email,
        'integration_allowed_team_ids' => (string) $team->id,
        'roles' => [$role->id],
        'send_password_setup' => true,
    ])->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

    $user = User::query()->where('email', $email)->firstOrFail();
    expect($user->roles()->pluck('name')->all())->toBe(['Integration API']);
    expect($user->team_id)->toBeNull()->and($user->integration_allowed_team_ids)->toBe([$team->id]);
    Notification::assertSentTo($user, AccountPasswordLink::class);
    $notification = Notification::sent($user, AccountPasswordLink::class)->sole();
    $mail = $notification->toMail($user);
    expect($mail->actionText)->toBe('Set your password')
        ->and($mail->actionUrl)->toContain('/set-up-password/')
        ->and(implode(' ', $mail->outroLines))->toContain('12 hours', 'reply to this email')
        ->and($mail->replyTo[0][0])->toBe($admin->email);

    $expiredToken = Password::broker('account_setup')->createToken($admin);

    Auth::guard('web')->logout();
    $this->travel(11)->hours();
    $this->get($mail->actionUrl)->assertOk();
    $payload = [
        'token' => $notification->token,
        'email' => $email,
        'password' => 'my-new-password',
        'password_confirmation' => 'my-new-password',
    ];
    $this->post(route('password.store'), $payload)->assertSessionHasErrors('email');
    $this->post(route('password.setup.store'), $payload)->assertRedirect(route('login'));
    expect(Hash::check($payload['password'], $user->fresh()->password))->toBeTrue();
    $this->post(route('password.setup.store'), $payload)->assertSessionHasErrors('email');
    $this->travel(61)->minutes();
    $this->post(route('password.setup.store'), array_replace($payload, [
        'email' => $admin->email,
        'token' => $expiredToken,
    ]))->assertSessionHasErrors('email');
    $this->post(route('login'), [
        'email' => $email,
        'password' => $payload['password'],
    ])->assertRedirect(route('integration.everlytic.docs'));
});

it('preserves manual creation and keeps an invited account when mail fails', function () {
    Notification::fake();
    $team = Team::query()->create(['name' => 'Mail Failure Site']);
    $admin = createEverlyticAdmin($team);
    $role = Role::query()->where('name', 'Integration API')->firstOrFail();
    config(['panel.registration_default_role' => $role->id]);
    $attributes = ['name' => 'New User', 'surname' => 'Example', 'integration_allowed_team_ids' => (string) $team->id, 'roles' => [$role->id]];

    $this->actingAs($admin)->post(route('admin.users.store'), $attributes + [
        'email' => 'manual@example.test',
    ])->assertSessionHasErrors('password');

    $this->post(route('admin.users.store'), $attributes + [
        'email' => 'manual@example.test',
        'password' => 'manual-password',
    ])->assertRedirect(route('admin.users.index'));
    Notification::assertNothingSent();

    Password::shouldReceive('broker')->with('account_setup')->once()->andReturnSelf();
    Password::shouldReceive('sendResetLink')->once()->andThrow(new RuntimeException('Test mail failure'));
    $this->post(route('admin.users.store'), $attributes + [
        'email' => 'mail-failure@example.test',
        'send_password_setup' => true,
    ])->assertRedirect(route('admin.users.index'))->assertSessionHas('error');
    $this->assertDatabaseHas('users', ['email' => 'mail-failure@example.test']);
});

it('issues and rotates a non-expiring token for an existing restricted user', function () {
    $team = Team::query()->create(['name' => 'Token Site']);
    $user = createEverlyticIntegrationUser($team, 'Token.User@Example.test');

    expect(Artisan::call('integrations:issue-everlytic-token', [
        '--email' => 'token.user@example.test',
        '--name' => 'old',
    ]))->toBe(0);
    expect(Artisan::call('integrations:issue-everlytic-token', [
        '--email' => 'token.user@example.test',
        '--name' => 'staging',
        '--revoke-existing' => true,
    ]))->toBe(0);

    $token = PersonalAccessToken::query()->where('tokenable_id', $user->id)->sole();

    expect($token->name)->toBe('everlytic-staging')
        ->and($token->abilities)->toBe([AttendedGuestFeedService::TOKEN_ABILITY])
        ->and($token->expires_at)->toBeNull();
});

it('refuses token issuance for a user with any non-integration role', function () {
    $team = Team::query()->create(['name' => 'Mixed Role Site']);
    $user = createEverlyticIntegrationUser($team, 'mixed-role@example.test');
    $otherRole = Role::query()->create(['name' => 'Other Role']);
    $user->roles()->attach($otherRole);

    expect(Artisan::call('integrations:issue-everlytic-token', [
        '--email' => $user->email,
    ]))->toBe(1)
        ->and(PersonalAccessToken::query()->count())->toBe(0);
});
