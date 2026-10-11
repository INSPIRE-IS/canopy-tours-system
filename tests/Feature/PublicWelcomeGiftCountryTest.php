<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('provides phone countries to the welcome gift purchase modal', function () {
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Gift Test Site',
        'is_published' => true,
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

    $this->get(route('front.welcome', ['team_id' => $teamId]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('countries.0.id', $countryId)
            ->where('countries.0.name', 'South Africa')
            ->where('countries.0.phonecode', '27'));
});
