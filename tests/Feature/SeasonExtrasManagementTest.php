<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

function createSeasonExtrasManager(): array
{
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Extras Test Team',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $roleId = DB::table('roles')->insertGetId([
        'id' => 2,
        'name' => 'Manager',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'seasons_edit',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('permission_role')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Season',
        'surname' => 'Manager',
        'email' => 'season-manager@example.test',
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('role_user')->insert([
        'user_id' => $userId,
        'role_id' => $roleId,
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Extras Test Service',
        'capacity' => 8,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => '2032',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => false,
        'include_public_extras' => false,
        'status' => false,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [User::query()->findOrFail($userId), $seasonId, $serviceId, $teamId];
}

function seasonUpdatePayload(int $serviceId, int $userId, bool $includeExtras, array $extras): array
{
    return [
        'service_id' => $serviceId,
        'name' => '2032',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => false,
        'include_public_extras' => $includeExtras,
        'status' => false,
        'updated_by_id' => $userId,
        'extras' => $extras,
    ];
}

it('creates updates and soft deletes season extras while retaining hidden configuration', function () {
    [$user, $seasonId, $serviceId, $teamId] = createSeasonExtrasManager();

    $removedId = DB::table('season_extras')->insertGetId([
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'name' => 'Old meal',
        'description' => null,
        'unit_price' => 90,
        'is_per_guest' => true,
        'is_active' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $keptId = DB::table('season_extras')->insertGetId([
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'name' => 'Video',
        'description' => null,
        'unit_price' => 150,
        'is_per_guest' => false,
        'is_active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $extras = [
        [
            'id' => $keptId,
            'name' => 'Tour video',
            'description' => 'One package per booking.',
            'unit_price' => '175.50',
            'is_per_guest' => false,
            'is_active' => true,
        ],
        [
            'id' => null,
            'name' => 'Braai',
            'description' => 'Prepared for each booked guest.',
            'unit_price' => '120',
            'is_per_guest' => true,
            'is_active' => true,
        ],
    ];

    $this->actingAs($user)
        ->put(route('admin.seasons.update', $seasonId), seasonUpdatePayload($serviceId, $user->id, true, $extras))
        ->assertRedirect(route('admin.seasons.index'));

    $this->assertDatabaseHas('seasons', [
        'id' => $seasonId,
        'include_public_extras' => true,
    ])->assertDatabaseHas('season_extras', [
        'id' => $keptId,
        'name' => 'Tour video',
        'unit_price' => 175.50,
        'is_per_guest' => false,
        'sort_order' => 0,
        'deleted_at' => null,
    ])->assertDatabaseHas('season_extras', [
        'season_id' => $seasonId,
        'name' => 'Braai',
        'unit_price' => 120,
        'is_per_guest' => true,
        'sort_order' => 1,
        'deleted_at' => null,
    ]);
    expect(DB::table('season_extras')->where('id', $removedId)->whereNotNull('deleted_at')->exists())->toBeTrue();

    $savedExtras = DB::table('season_extras')
        ->where('season_id', $seasonId)
        ->whereNull('deleted_at')
        ->orderBy('sort_order')
        ->get()
        ->map(fn ($extra) => [
            'id' => $extra->id,
            'name' => $extra->name,
            'description' => $extra->description,
            'unit_price' => $extra->unit_price,
            'is_per_guest' => (bool) $extra->is_per_guest,
            'is_active' => (bool) $extra->is_active,
        ])
        ->all();

    $this->actingAs($user)
        ->put(route('admin.seasons.update', $seasonId), seasonUpdatePayload($serviceId, $user->id, false, $savedExtras))
        ->assertRedirect(route('admin.seasons.index'));

    $this->assertDatabaseHas('seasons', [
        'id' => $seasonId,
        'include_public_extras' => false,
    ]);
    expect(DB::table('season_extras')->where('season_id', $seasonId)->whereNull('deleted_at')->count())->toBe(2);

    $legacyPayload = seasonUpdatePayload($serviceId, $user->id, false, []);
    unset($legacyPayload['extras']);
    $legacyPayload['name'] = '2032 updated';

    $this->actingAs($user)
        ->put(route('admin.seasons.update', $seasonId), $legacyPayload)
        ->assertRedirect(route('admin.seasons.index'));

    $this->assertDatabaseHas('seasons', ['id' => $seasonId, 'name' => '2032 updated']);
    expect(DB::table('season_extras')->where('season_id', $seasonId)->whereNull('deleted_at')->count())->toBe(2);
});

it('rejects enabled extras with invalid names descriptions or prices', function () {
    [$user, $seasonId, $serviceId] = createSeasonExtrasManager();

    $payload = seasonUpdatePayload($serviceId, $user->id, true, [[
        'id' => null,
        'name' => '',
        'description' => str_repeat('x', 256),
        'unit_price' => '0',
        'is_per_guest' => true,
        'is_active' => true,
    ]]);

    $this->actingAs($user)
        ->from(route('admin.seasons.index'))
        ->put(route('admin.seasons.update', $seasonId), $payload)
        ->assertRedirect(route('admin.seasons.index'))
        ->assertSessionHasErrors([
            'extras.0.name',
            'extras.0.description',
            'extras.0.unit_price',
        ]);
});
