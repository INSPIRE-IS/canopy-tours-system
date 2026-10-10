<?php

use App\Models\Service;
use App\Services\Calendar\PublicServiceAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function createPublicAvailabilitySeason(
    int $teamId,
    int $serviceId,
    string $name,
    string $startDate,
    string $endDate,
    float $adultRate,
    string $time,
    bool $internalOnly = false,
    bool $overrideOnly = false
): array {
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => $name,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'rake_rate_adult' => $adultRate,
        'rake_rate_child' => $adultRate - 100,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => $internalOnly,
        'status' => true,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $inventoryId = DB::table('inventories')->insertGetId([
        'allow_internal_override' => false,
        'slot_cutoff_hours' => 0,
        'slot_funneling' => 0,
        'service_id' => $serviceId,
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach (range(1, 7) as $dayId) {
        DB::table('inventory_type_days')->insert([
            'inventory_id' => $inventoryId,
            'type_day_id' => $dayId,
            'team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $slotId = DB::table('inventory_time_slots')->insertGetId([
        'start_time' => $time,
        'is_override_only' => $overrideOnly,
        'inventory_id' => $inventoryId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('seasonId', 'inventoryId', 'slotId');
}

function createPublicAvailabilityContext(): array
{
    foreach (range(1, 7) as $dayId) {
        DB::table('type_days')->insert([
            'id' => $dayId,
            'name' => CarbonImmutable::now()->startOfWeek()->addDays($dayId - 1)->format('l'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Karkloof Canopy Tour',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Canopy Tour',
        'pillbox_conditions' => 'Arrive 20 minutes before the time.',
        'capacity' => 8,
        'is_online_bookable' => true,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$teamId, $serviceId];
}

it('returns owned slots across adjacent public seasons while preserving a real gap', function () {
    [$teamId, $serviceId] = createPublicAvailabilityContext();


    $oldSeason = createPublicAvailabilitySeason(
        $teamId,
        $serviceId,
        '2030',
        '2030-01-01',
        '2030-12-31',
        895,
        '09:00:00'
    );
    $newSeason = createPublicAvailabilitySeason(
        $teamId,
        $serviceId,
        '2031',
        '2031-01-02',
        '2031-12-31',
        950,
        '09:30:00'
    );

    $response = app(PublicServiceAvailability::class)->build(
        Service::query()->findOrFail($serviceId),
        CarbonImmutable::parse('2030-12-31'),
        CarbonImmutable::parse('2031-01-02'),
        2,
        0
    );

    $days = collect($response['days'])->keyBy('date');

    expect($response['service_id'])->toBe($serviceId)
        ->and($response['requested_pax'])->toBe(['adults' => 2, 'children' => 0])
        ->and($days['2030-12-31'])->toMatchArray([
            'status' => 'available',
            'is_open' => true,
            'from_price' => 895.0,
            'slot_count' => 1,
        ])
        ->and($response['slots_by_date']['2030-12-31'][0])->toMatchArray([
            'season_id' => $oldSeason['seasonId'],
            'inventory_id' => $oldSeason['inventoryId'],
            'slot_id' => $oldSeason['slotId'],
            'time' => '09:00:00',
            'arrival_by' => '08:40',
            'adult_price' => 895.0,
        ])
        ->and($days['2031-01-01'])->toMatchArray([
            'status' => 'unavailable',
            'is_open' => false,
            'from_price' => null,
            'slot_count' => 0,
        ])
        ->and($response['slots_by_date'])->not->toHaveKey('2031-01-01')
        ->and($response['slots_by_date']['2031-01-02'][0])->toMatchArray([
            'season_id' => $newSeason['seasonId'],
            'inventory_id' => $newSeason['inventoryId'],
            'slot_id' => $newSeason['slotId'],
            'time' => '09:30:00',
            'arrival_by' => '09:10',
            'adult_price' => 950.0,
        ]);
});

it('does not lose February dates when navigating adjacent two-month windows', function () {
    [$teamId, $serviceId] = createPublicAvailabilityContext();

    createPublicAvailabilitySeason(
        $teamId,
        $serviceId,
        '2031',
        '2031-01-01',
        '2031-12-31',
        950,
        '09:30:00'
    );

    $service = Service::query()->findOrFail($serviceId);
    $januaryFebruary = app(PublicServiceAvailability::class)->build(
        $service,
        CarbonImmutable::parse('2031-01-01'),
        CarbonImmutable::parse('2031-02-28'),
        2,
        0
    );
    $februaryMarch = app(PublicServiceAvailability::class)->build(
        $service,
        CarbonImmutable::parse('2031-02-01'),
        CarbonImmutable::parse('2031-03-31'),
        2,
        0
    );
    $januaryFebruaryDays = collect($januaryFebruary['days'])->keyBy('date');
    $februaryMarchDays = collect($februaryMarch['days'])->keyBy('date');

    expect($januaryFebruaryDays['2031-02-03']['status'])->toBe('available')
        ->and($januaryFebruaryDays['2031-02-28']['status'])->toBe('available')
        ->and($februaryMarchDays['2031-02-03']['status'])->toBe('available')
        ->and($februaryMarchDays['2031-02-28']['status'])->toBe('available')
        ->and($februaryMarchDays['2031-03-03']['status'])->toBe('available')
        ->and($januaryFebruary['slots_by_date'])->toHaveKeys(['2031-02-03', '2031-02-28'])
        ->and($februaryMarch['slots_by_date'])->toHaveKeys(['2031-02-03', '2031-02-28', '2031-03-03']);
});

it('keeps season inventory and slot ownership when adjacent seasons use the same displayed time', function () {
    [$teamId, $serviceId] = createPublicAvailabilityContext();

    $oldSeason = createPublicAvailabilitySeason($teamId, $serviceId, '2030', '2030-01-01', '2030-12-31', 895, '09:00:00');
    $newSeason = createPublicAvailabilitySeason($teamId, $serviceId, '2031', '2031-01-01', '2031-12-31', 950, '09:00:00');

    $response = app(PublicServiceAvailability::class)->build(
        Service::query()->findOrFail($serviceId),
        CarbonImmutable::parse('2030-12-31'),
        CarbonImmutable::parse('2031-01-01'),
        2,
        0
    );

    expect($response['slots_by_date']['2030-12-31'][0])->toMatchArray([
        'season_id' => $oldSeason['seasonId'],
        'inventory_id' => $oldSeason['inventoryId'],
        'slot_id' => $oldSeason['slotId'],
        'time' => '09:00:00',
        'adult_price' => 895.0,
    ])->and($response['slots_by_date']['2031-01-01'][0])->toMatchArray([
        'season_id' => $newSeason['seasonId'],
        'inventory_id' => $newSeason['inventoryId'],
        'slot_id' => $newSeason['slotId'],
        'time' => '09:00:00',
        'adult_price' => 950.0,
    ])->and($oldSeason['slotId'])->not->toBe($newSeason['slotId']);
});

it('applies public capacity overrides while excluding internal-only overrides', function () {
    [$teamId, $serviceId] = createPublicAvailabilityContext();

    $season = createPublicAvailabilitySeason($teamId, $serviceId, '2032', '2032-01-01', '2032-12-31', 995, '09:00:00');

    DB::table('inventory_slot_ranges')->insert([
        [
            'inventory_id' => $season['inventoryId'],
            'time_slot_id' => $season['slotId'],
            'start_date' => '2032-01-10',
            'end_date' => '2032-01-10',
            'internal_only' => false,
            'capacity_override' => 2,
            'team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'inventory_id' => $season['inventoryId'],
            'time_slot_id' => $season['slotId'],
            'start_date' => '2032-01-11',
            'end_date' => '2032-01-11',
            'internal_only' => true,
            'capacity_override' => 1,
            'team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $response = app(PublicServiceAvailability::class)->build(
        Service::query()->findOrFail($serviceId),
        CarbonImmutable::parse('2032-01-10'),
        CarbonImmutable::parse('2032-01-11'),
        3,
        0
    );
    $days = collect($response['days'])->keyBy('date');

    expect($days['2032-01-10'])->toMatchArray([
        'status' => 'full',
        'is_open' => false,
        'slot_count' => 0,
    ])->and($response['slots_by_date'])->not->toHaveKey('2032-01-10')
        ->and($days['2032-01-11'])->toMatchArray([
            'status' => 'available',
            'is_open' => true,
            'slot_count' => 1,
        ])->and($response['slots_by_date']['2032-01-11'][0]['spaces_remaining'])->toBe(8);
});

it('lets a public date override reopen a slot closed by a closeout', function () {
    [$teamId, $serviceId] = createPublicAvailabilityContext();

    $season = createPublicAvailabilitySeason($teamId, $serviceId, '2032', '2032-01-01', '2032-12-31', 995, '09:00:00');

    DB::table('closeouts')->insert([
        'name' => 'Maintenance',
        'start_date' => '2032-01-12',
        'end_date' => '2032-01-13',
        'reason' => 'Regression fixture',
        'service_id' => $serviceId,
        'season_id' => $season['seasonId'],
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('inventory_slot_ranges')->insert([
        'inventory_id' => $season['inventoryId'],
        'time_slot_id' => $season['slotId'],
        'start_date' => '2032-01-13',
        'end_date' => '2032-01-13',
        'internal_only' => false,
        'capacity_override' => 8,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = app(PublicServiceAvailability::class)->build(
        Service::query()->findOrFail($serviceId),
        CarbonImmutable::parse('2032-01-12'),
        CarbonImmutable::parse('2032-01-13'),
        2,
        0
    );
    $days = collect($response['days'])->keyBy('date');

    expect($days['2032-01-12'])->toMatchArray([
        'status' => 'unavailable',
        'is_open' => false,
    ])->and($response['slots_by_date'])->not->toHaveKey('2032-01-12')
        ->and($days['2032-01-13'])->toMatchArray([
            'status' => 'available',
            'is_open' => true,
        ])->and($response['slots_by_date'])->toHaveKey('2032-01-13');
});

it('does not expose staff-only seasons to public availability', function () {
    [$teamId, $serviceId] = createPublicAvailabilityContext();

    createPublicAvailabilitySeason($teamId, $serviceId, 'Internal', '2032-01-01', '2032-12-31', 500, '08:00:00', true);

    $response = app(PublicServiceAvailability::class)->build(
        Service::query()->findOrFail($serviceId),
        CarbonImmutable::parse('2032-02-01'),
        CarbonImmutable::parse('2032-02-01'),
        2,
        0
    );

    expect($response['days'][0])->toMatchArray([
        'date' => '2032-02-01',
        'status' => 'unavailable',
        'is_open' => false,
        'slot_count' => 0,
    ])->and($response['slots_by_date'])->toBe([]);
});
