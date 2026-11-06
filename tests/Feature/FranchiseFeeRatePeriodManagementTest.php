<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

function createFranchisePeriodUser(bool $isAdmin): array
{
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Franchise Period Test Site',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $roleId = $isAdmin ? 1 : 2;
    DB::table('roles')->insert([
        'id' => $roleId,
        'name' => $isAdmin ? 'Admin' : 'Manager',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'teams_access',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('permission_role')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Franchise',
        'surname' => 'Manager',
        'email' => ($isAdmin ? 'admin' : 'manager').'-franchise-period@example.test',
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

    return [
        'team_id' => $teamId,
        'user' => User::query()->findOrFail($userId),
    ];
}

it('allows a site admin to add adjacent franchise fee periods', function () {
    $fixture = createFranchisePeriodUser(true);
    $this->actingAs($fixture['user']);

    $this->post(route('admin.settings.franchise-fee-periods.store'), [
        'royalty_percent' => 10,
        'marketing_percent' => 1.5,
        'effective_from' => '2020-01-01',
        'effective_to' => '2026-10-31',
        'is_active' => true,
        'note' => 'Historical rate',
    ])->assertRedirect();

    $this->post(route('admin.settings.franchise-fee-periods.store'), [
        'royalty_percent' => 9,
        'marketing_percent' => 1.5,
        'effective_from' => '2026-11-01',
        'effective_to' => null,
        'is_active' => true,
        'note' => 'Current rate',
    ])->assertRedirect();

    $this->assertDatabaseHas('franchise_fee_rate_periods', [
        'team_id' => $fixture['team_id'],
        'royalty_percent' => 10.00,
        'marketing_percent' => 1.50,
        'effective_to' => '2026-10-31',
    ]);
    $this->assertDatabaseHas('franchise_fee_rate_periods', [
        'team_id' => $fixture['team_id'],
        'royalty_percent' => 9.00,
        'marketing_percent' => 1.50,
        'effective_from' => '2026-11-01',
    ]);
});

it('rejects overlapping active franchise fee periods', function () {
    $fixture = createFranchisePeriodUser(true);
    $this->actingAs($fixture['user']);

    $this->post(route('admin.settings.franchise-fee-periods.store'), [
        'royalty_percent' => 10,
        'marketing_percent' => 1.5,
        'effective_from' => '2020-01-01',
        'effective_to' => '2026-10-31',
        'is_active' => true,
    ])->assertRedirect();

    $this->post(route('admin.settings.franchise-fee-periods.store'), [
        'royalty_percent' => 9,
        'marketing_percent' => 1.5,
        'effective_from' => '2026-10-31',
        'effective_to' => null,
        'is_active' => true,
    ])->assertSessionHasErrors('effective_from');

    expect(DB::table('franchise_fee_rate_periods')->where('team_id', $fixture['team_id'])->count())->toBe(1);
});

it('blocks non-admin staff from managing franchise fee periods', function () {
    $fixture = createFranchisePeriodUser(false);

    $this->actingAs($fixture['user'])
        ->post(route('admin.settings.franchise-fee-periods.store'), [
            'royalty_percent' => 9,
            'marketing_percent' => 1.5,
            'effective_from' => '2026-11-01',
            'is_active' => true,
        ])
        ->assertForbidden();
});

it('seeds the approved Karkloof rate transition without overlapping dates', function () {
    DB::table('teams')->insert([
        'id' => 3,
        'name' => 'Karkloof Canopy Tour',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_11_05_000003_seed_karkloof_franchise_fee_rate_periods.php');
    $migration->up();

    $this->assertDatabaseHas('franchise_fee_rate_periods', [
        'team_id' => 3,
        'royalty_percent' => 10.00,
        'marketing_percent' => 1.50,
        'effective_from' => '2020-01-01',
        'effective_to' => '2026-10-31',
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('franchise_fee_rate_periods', [
        'team_id' => 3,
        'royalty_percent' => 9.00,
        'marketing_percent' => 1.50,
        'effective_from' => '2026-11-01',
        'effective_to' => null,
        'is_active' => true,
    ]);
});
