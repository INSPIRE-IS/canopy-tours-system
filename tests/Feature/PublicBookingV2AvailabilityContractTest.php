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
    string $time
): array {
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => $name,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'rake_rate_adult' => $adultRate,
        'rake_rate_child' => $adultRate - 100,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => false,
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
        'is_override_only' => false,
        'inventory_id' => $inventoryId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('seasonId', 'inventoryId', 'slotId');
}

it('returns owned slots across adjacent public seasons while preserving a real gap', function () {
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
