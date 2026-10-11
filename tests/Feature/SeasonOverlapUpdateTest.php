<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

function createSeasonUpdateContext(): array
{
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Season Test Site',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Season',
        'surname' => 'Editor',
        'email' => 'season-editor@example.test',
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Season Test Service',
        'capacity' => 8,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Gate::define('seasons_edit', fn () => true);

    return [$teamId, $serviceId, User::query()->findOrFail($userId)];
}

function createSeasonForUpdate(int $teamId, int $serviceId, string $name, string $start, string $end): int
{
    return DB::table('seasons')->insertGetId([
        'name' => $name,
        'start_date' => $start,
        'end_date' => $end,
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => false,
        'status' => true,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function seasonUpdatePayload(int $serviceId, int $userId, string $name, string $start, string $end): array
{
    return [
        'service_id' => $serviceId,
        'name' => $name,
        'start_date' => $start,
        'end_date' => $end,
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => false,
        'status' => true,
        'updated_by_id' => $userId,
    ];
}

it('allows an unrelated edit when the existing season dates already overlap', function () {
    [$teamId, $serviceId, $user] = createSeasonUpdateContext();
    $seasonId = createSeasonForUpdate($teamId, $serviceId, 'Original', '2030-01-01', '2030-12-31');
    createSeasonForUpdate($teamId, $serviceId, 'Historical overlap', '2030-06-01', '2031-05-31');

    $this->actingAs($user)
        ->put(route('admin.seasons.update', $seasonId), seasonUpdatePayload(
            $serviceId,
            $user->id,
            'Updated name only',
            '2030-01-01',
            '2030-12-31'
        ))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.seasons.index'));

    expect(DB::table('seasons')->where('id', $seasonId)->value('name'))->toBe('Updated name only');
});

it('rejects a date change that creates an overlap', function () {
    [$teamId, $serviceId, $user] = createSeasonUpdateContext();
    $seasonId = createSeasonForUpdate($teamId, $serviceId, 'First', '2030-01-01', '2030-06-30');
    createSeasonForUpdate($teamId, $serviceId, 'Second', '2030-07-01', '2030-12-31');

    $this->actingAs($user)
        ->from(route('admin.seasons.index'))
        ->put(route('admin.seasons.update', $seasonId), seasonUpdatePayload(
            $serviceId,
            $user->id,
            'First',
            '2030-01-01',
            '2030-07-15'
        ))
        ->assertSessionHasErrors('start_date')
        ->assertRedirect(route('admin.seasons.index'));

    expect(DB::table('seasons')->where('id', $seasonId)->value('end_date'))->toBe('2030-06-30');
});

it('allows a date change that remains outside the next season', function () {
    [$teamId, $serviceId, $user] = createSeasonUpdateContext();
    $seasonId = createSeasonForUpdate($teamId, $serviceId, 'First', '2030-01-01', '2030-06-30');
    createSeasonForUpdate($teamId, $serviceId, 'Second', '2030-09-01', '2030-12-31');

    $this->actingAs($user)
        ->put(route('admin.seasons.update', $seasonId), seasonUpdatePayload(
            $serviceId,
            $user->id,
            'First extended',
            '2030-01-01',
            '2030-08-31'
        ))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.seasons.index'));

    expect(DB::table('seasons')->where('id', $seasonId)->value('end_date'))->toBe('2030-08-31');
});
