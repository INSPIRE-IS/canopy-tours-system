<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Continent;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class SyncGeoData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-geo-data'; // php artisan app:sync-geo-data

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching country data...');
        ini_set('memory_limit', '512M'); // or '1G' for 1GB if needed
        $countries = Http::get('https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries.json')->json();


        $continentMap = [];

        foreach ($countries as $country) {
            $continentName = $country['region'] ?? 'Unknown';
            if (!isset($continentMap[$continentName])) {
                $continent = Continent::firstOrCreate(['name' => $continentName]);
                $continentMap[$continentName] = $continent->id;
            }

            Country::updateOrCreate(
                ['iso2' => $country['iso2']],
                [
                    'name' => $country['name'],
                    'iso3' => $country['iso3'],
                    'continent_id' => $continentMap[$continentName],
                    'nationality' => $country['nationality'],
                    'phonecode' => $country['phonecode'],
                ]
            );
        }
        $this->info('Countries and continents synced.');
        $states = Http::get('https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/states.json')->json();

        foreach ($states as $state) {

            $country = Country::where('iso2', $state['country_code'])->first();
            if (!$country) continue;

            State::updateOrCreate(
                ['name' => $state['name'], 'country_id' => $country->id],
                ['type' => $state['type']]
            );
        }

        $this->info('States synced.');
        $cities = Http::get('https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/cities.json')->json();
        foreach ($cities as $city) {

            $state = State::where('name', $city['state_name'])
                ->whereHas('country', fn($q) => $q->where('iso2', $city['country_code']))
                ->first();

            if (!$state) continue;

            City::updateOrCreate(
                ['name' => $city['name'], 'state_id' => $state->id],
                []
            );
        }

        $this->info('Cities synced.');
    }
}
