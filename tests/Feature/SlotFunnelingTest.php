<?php

use App\Models\Service;
use App\Services\Calendar\PublicServiceAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function createSlotFunnelingFixture(int $maxOpenSlots = 1): array
{
    DB::table('status_payments')->insert([
        'id' => 2,
        'name' => 'Paid',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach (range(1, 7) as $dayId) {
        DB::table('type_days')->insert([
            'id' => $dayId,
            'name' => CarbonImmutable::now()->startOfWeek()->addDays($dayId - 1)->format('l'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Slot Funnel Test Site',
        'is_published' => true,
        'frontend_hold_enabled' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Canopy Tour',
        'capacity' => 8,
        'is_online_bookable' => true,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => '2032',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'rake_rate_adult' => 995,
        'rake_rate_child' => 895,
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
        'slot_funneling' => $maxOpenSlots,
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

    $slotIds = [];
    foreach (['09:00:00', '10:00:00', '11:00:00', '12:00:00'] as $time) {
        $slotIds[$time] = DB::table('inventory_time_slots')->insertGetId([
            'start_time' => $time,
            'is_override_only' => false,
            'inventory_id' => $inventoryId,
            'team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return compact('teamId', 'serviceId', 'seasonId', 'inventoryId', 'slotIds');
}

function createSlotFunnelingBooking(array $fixture, string $time, int $pax): void
{
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => 'Slot Funnel Guest',
        'status_payment_id' => 2,
        'tot_paid' => 995,
        'grand_tot_due' => 995,
        'reference' => 'REF-'.Str::upper(Str::random(8)),
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingItemId = DB::table('booking_items')->insertGetId([
        'booking_id' => $bookingId,
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'inventory_id' => $fixture['inventoryId'],
        'pax_adults' => $pax,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingDetailId = DB::table('booking_details')->insertGetId([
        'booking_item_id' => $bookingItemId,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('booking_inventories')->insert([
        'date' => '2032-02-10',
        'adults' => $pax,
        'children' => 0,
        'guides' => 0,
        'pax' => $pax,
        'booking_item_id' => $bookingItemId,
        'booking_detail_id' => $bookingDetailId,
        'inventory_type_day_id' => DB::table('inventory_type_days')
            ->where('inventory_id', $fixture['inventoryId'])
            ->value('id'),
        'inventory_time_slot_id' => $fixture['slotIds'][$time],
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function slotFunnelingAvailability(array $fixture, int $requestedPax): array
{
    $date = CarbonImmutable::parse('2032-02-10', 'Africa/Johannesburg');

    return app(PublicServiceAvailability::class)->build(
        Service::query()->findOrFail($fixture['serviceId']),
        $date,
        $date,
        $requestedPax,
        0
    );
}

function availableSlotTimes(array $availability): array
{
    return collect($availability['slots_by_date']['2032-02-10'] ?? [])
        ->pluck('time')
        ->all();
}

it('limits an empty day to the configured number of earliest open slots', function () {
    $fixture = createSlotFunnelingFixture(2);

    expect(availableSlotTimes(slotFunnelingAvailability($fixture, 2)))
        ->toBe(['09:00:00', '10:00:00']);
});

it('keeps a partially filled earlier slot open when the requested party still fits', function () {
    $fixture = createSlotFunnelingFixture(1);
    createSlotFunnelingBooking($fixture, '09:00:00', 6);

    $availability = slotFunnelingAvailability($fixture, 2);

    expect(availableSlotTimes($availability))->toBe(['09:00:00'])
        ->and($availability['slots_by_date']['2032-02-10'][0]['spaces_remaining'])->toBe(2);
});

it('advances the funnel when the earlier slot cannot fit the requested party', function () {
    $fixture = createSlotFunnelingFixture(1);
    createSlotFunnelingBooking($fixture, '09:00:00', 6);

    expect(availableSlotTimes(slotFunnelingAvailability($fixture, 3)))
        ->toBe(['10:00:00']);
});

it('counts active holds and ignores expired holds when advancing the funnel', function () {
    $fixture = createSlotFunnelingFixture(1);
    $baseHold = [
        'session_id' => 'other-browser',
        'team_id' => $fixture['teamId'],
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'inventory_id' => $fixture['inventoryId'],
        'inventory_time_slot_id' => $fixture['slotIds']['09:00:00'],
        'booking_date' => '2032-02-10',
        'adults' => 6,
        'children' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ];

    DB::table('public_booking_holds')->insert([
        ...$baseHold,
        'uuid' => (string) Str::uuid(),
        'expires_at' => now()->addMinutes(15),
    ]);

    expect(availableSlotTimes(slotFunnelingAvailability($fixture, 3)))
        ->toBe(['10:00:00']);

    DB::table('public_booking_holds')->update(['expires_at' => now()->subSecond()]);

    expect(availableSlotTimes(slotFunnelingAvailability($fixture, 3)))
        ->toBe(['09:00:00']);
});

it('shows all qualifying slots when funneling is disabled', function () {
    $fixture = createSlotFunnelingFixture(0);

    expect(availableSlotTimes(slotFunnelingAvailability($fixture, 2)))
        ->toBe(['09:00:00', '10:00:00', '11:00:00', '12:00:00']);
});

it('does not let a closed earlier slot consume the open-slot quota', function () {
    $fixture = createSlotFunnelingFixture(1);
    $closeoutId = DB::table('closeouts')->insertGetId([
        'name' => 'Morning maintenance',
        'start_date' => '2032-02-10',
        'end_date' => '2032-02-10',
        'reason' => 'Test closeout',
        'priority' => 50,
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('closeout_day_time_slots')->insert([
        'closeout_id' => $closeoutId,
        'day_id' => CarbonImmutable::parse('2032-02-10')->dayOfWeekIso,
        'time_slot_id' => $fixture['slotIds']['09:00:00'],
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(availableSlotTimes(slotFunnelingAvailability($fixture, 2)))
        ->toBe(['10:00:00']);
});
