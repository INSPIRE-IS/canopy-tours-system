<?php

use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => CarbonImmutable::setTestNow('2026-10-11 09:00:00'));
afterEach(fn () => CarbonImmutable::setTestNow());

it('shows the current public season and international phone countries on gift checkout', function () {
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Gift Checkout Site',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Gift Checkout Tour',
        'capacity' => 8,
        'is_online_bookable' => true,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $expiredSeasonId = DB::table('seasons')->insertGetId([
        'name' => 'Expired Season',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'rake_rate_adult' => 700,
        'rake_rate_child' => 600,
        'rake_rate_guide' => 0,
        'status' => true,
        'internal_only' => false,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $currentSeasonId = DB::table('seasons')->insertGetId([
        'name' => 'Current Gift Season',
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'status' => true,
        'internal_only' => false,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $continentId = DB::table('continents')->insertGetId([
        'name' => 'Africa',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $countryId = DB::table('countries')->insertGetId([
        'continent_id' => $continentId,
        'name' => 'South Africa',
        'iso2' => 'ZA',
        'iso3' => 'ZAF',
        'phonecode' => '27',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('front.v2.gift', ['team' => $teamId, 'service' => $serviceId]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('PublicBookingV2/GiftCheckout')
            ->where('team.id', $teamId)
            ->where('service.id', $serviceId)
            ->where('season.id', $currentSeasonId)
            ->where('season.adult_price', 895)
            ->where('season.child_price', 795)
            ->where('countries.0.id', $countryId)
            ->where('countries.0.phonecode', '27'));

    expect($expiredSeasonId)->not->toBe($currentSeasonId);
});

it('rejects a gift checkout when the service belongs to another site', function () {
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Gift Checkout Site A',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $otherTeamId = DB::table('teams')->insertGetId([
        'name' => 'Gift Checkout Site B',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Other Site Tour',
        'capacity' => 8,
        'is_online_bookable' => true,
        'team_id' => $otherTeamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('front.v2.gift', ['team' => $teamId, 'service' => $serviceId]))
        ->assertNotFound();
});
