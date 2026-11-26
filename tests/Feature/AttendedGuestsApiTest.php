<?php

use App\Models\Role;
use App\Models\User;
use App\Services\AttendedGuestFeedService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::table('type_bookings')->insert([
        'id' => 1,
        'name' => 'Standard',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('status_payments')->insert([
        'id' => 1,
        'name' => 'Pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach ([
        'full_name',
        'email_address',
        'country_id',
        'province_id',
        'birthday_month',
        'birthday_day',
        'marketing_opt_in',
    ] as $slug) {
        DB::table('questions')->insert([
            'name' => str_replace('_', ' ', ucfirst($slug)),
            'slug' => $slug,
            'type' => 'indemnity',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
});

function createAttendedApiFixture(string $name): array
{
    $teamId = DB::table('teams')->insertGetId([
        'name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => "{$name} Service",
        'capacity' => 20,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => "{$name} Season",
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'rake_rate_adult' => 100,
        'rake_rate_child' => 50,
        'rake_rate_guide' => 0,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $inventoryId = DB::table('inventories')->insertGetId([
        'service_id' => $serviceId,
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => "{$name} Guest",
        'tot_paid' => 100,
        'grand_tot_due' => 100,
        'reference' => 'REF-'.strtoupper(substr(md5($name), 0, 6)),
        'type_booking_id' => 1,
        'status_payment_id' => 1,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingItemId = DB::table('booking_items')->insertGetId([
        'booking_id' => $bookingId,
        'service_id' => $serviceId,
        'season_id' => $seasonId,
        'inventory_id' => $inventoryId,
        'pax_adults' => 1,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingDetailId = DB::table('booking_details')->insertGetId([
        'booking_item_id' => $bookingItemId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('booking_inventories')->insert([
        'date' => '2032-11-19',
        'adults' => 1,
        'pax' => 1,
        'booking_item_id' => $bookingItemId,
        'booking_detail_id' => $bookingDetailId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('teamId', 'bookingId', 'bookingItemId');
}

function createAttendedApiToken(int $teamId, array $abilities = [AttendedGuestFeedService::TOKEN_ABILITY]): string
{
    $user = User::withoutEvents(fn () => User::query()->create([
        'name' => 'API',
        'surname' => 'Integration',
        'email' => "attended-api-{$teamId}-".uniqid().'@example.test',
        'password' => bcrypt(uniqid('password-', true)),
        'team_id' => null,
    ]));
    $user->forceFill(['integration_allowed_team_ids' => [$teamId]])->save();
    $user->roles()->attach(Role::query()->where('name', 'Integration API')->firstOrFail());

    return $user->createToken('test-token', $abilities)->plainTextToken;
}

function attendedApiUrl(int $teamId, string $from = '2032-11-01', string $to = '2032-11-30'): string
{
    return "/api/v1/attended-guests?team_id={$teamId}&from={$from}&to={$to}";
}

function createAttendedApiContact(
    array $fixture,
    string $name,
    string $email,
    string $consent = '1',
    string $attendanceStatus = 'arrived',
): array {
    $timestamp = CarbonImmutable::parse('2032-11-19 10:00:00', 'Africa/Johannesburg');
    $customerId = DB::table('customers')->insertGetId([
        'full_name' => $name,
        'email_address' => $email,
        'contact_number' => '+27821234567',
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);
    DB::table('customer_manifests')->insert([
        'position' => 1,
        'type' => 'adult',
        'is_indemnity' => 1,
        'attendance_status' => $attendanceStatus,
        'booking_item_id' => $fixture['bookingItemId'],
        'customer_id' => $customerId,
        'team_id' => $fixture['teamId'],
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ]);

    foreach ([
        'full_name' => $name,
        'email_address' => $email,
        'birthday_month' => '11',
        'birthday_day' => '19',
        'marketing_opt_in' => $consent,
    ] as $slug => $answer) {
        DB::table('question_answers')->insert([
            'answer' => $answer,
            'question_id' => DB::table('questions')->where('slug', $slug)->value('id'),
            'customer_id' => $customerId,
            'booking_item_id' => $fixture['bookingItemId'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    return compact('customerId');
}

it('requires a bearer token with only the Everlytic read ability', function () {
    $fixture = createAttendedApiFixture('Auth Site');
    $url = attendedApiUrl($fixture['teamId']);

    $this->getJson($url)->assertUnauthorized();
    $sessionUser = User::withoutEvents(fn () => User::query()->create([
        'name' => 'Session',
        'surname' => 'Only',
        'email' => 'session-only@example.test',
        'password' => bcrypt('password'),
        'team_id' => $fixture['teamId'],
    ]));
    $this->actingAs($sessionUser)->getJson($url)->assertForbidden();
    $this->withToken(createAttendedApiToken($fixture['teamId'], ['unrelated:read']))
        ->getJson($url)
        ->assertForbidden();
});

it('returns only opted-in arrived guests for the token site and date range', function () {
    $site = createAttendedApiFixture('Karkloof Test');
    $otherSite = createAttendedApiFixture('Other Site');
    $included = createAttendedApiContact($site, 'Alice Example', 'alice@example.test');
    createAttendedApiContact($site, 'Bob Withdrawn', 'bob@example.test', '2');
    createAttendedApiContact($site, 'Cara No Show', 'cara@example.test', '1', 'no_show');
    createAttendedApiContact($otherSite, 'Other Guest', 'other@example.test');

    $response = $this->withToken(createAttendedApiToken($site['teamId']))
        ->getJson(attendedApiUrl($site['teamId']));

    $response
        ->assertOk()
        ->assertHeader('cache-control', 'no-store, private')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.contact_id', "team-{$site['teamId']}-customer-{$included['customerId']}")
        ->assertJsonPath('data.0.full_name', 'Alice Example')
        ->assertJsonPath('data.0.first_name', 'Alice')
        ->assertJsonPath('data.0.last_name', 'Example')
        ->assertJsonPath('data.0.marketing_opt_in', true)
        ->assertJsonPath('data.0.birthday_month', 11)
        ->assertJsonPath('data.0.last_activity_date', '2032-11-19')
        ->assertJsonPath('meta.returned', 1)
        ->assertJsonPath('meta.has_more', false);
});

it('requires an allowed active site on every call and never falls back to the user team', function () {
    $one = createAttendedApiFixture('Site One');
    $two = createAttendedApiFixture('Site Two');
    $token = createAttendedApiToken($one['teamId']);
    $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)->tokenable;
    $user->forceFill(['team_id' => $two['teamId']])->save();

    $get = function (string $url) use ($token) {
        app('auth')->forgetGuards();

        return $this->withToken($token)->getJson($url);
    };
    $get('/api/v1/attended-guests?from=2032-11-01&to=2032-11-30')
        ->assertUnprocessable()->assertJsonValidationErrors('team_id');
    $get(attendedApiUrl($one['teamId']))->assertOk();
    $get(attendedApiUrl($two['teamId']))->assertForbidden();

    $user->forceFill(['integration_allowed_team_ids' => [$one['teamId'], $two['teamId']]])->save();
    $get(attendedApiUrl($two['teamId']))->assertOk();

    $user->forceFill(['integration_allowed_team_ids' => [$one['teamId']]])->save();
    $get(attendedApiUrl($two['teamId']))->assertForbidden();

    DB::table('teams')->where('id', $one['teamId'])->update(['deleted_at' => now()]);
    $get(attendedApiUrl($one['teamId']))->assertForbidden();
});

it('excludes records outside the inclusive activity range', function () {
    $site = createAttendedApiFixture('Date Range Site');
    createAttendedApiContact($site, 'Outside Guest', 'outside@example.test');

    $this->withToken(createAttendedApiToken($site['teamId']))
        ->getJson(attendedApiUrl($site['teamId'], '2032-11-20'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('uses the environment-controlled fixed cursor page size', function () {
    config()->set('services.everlytic.api_page_size', 1);
    $site = createAttendedApiFixture('Pagination Site');
    createAttendedApiContact($site, 'First Guest', 'first@example.test');
    createAttendedApiContact($site, 'Second Guest', 'second@example.test');
    $token = createAttendedApiToken($site['teamId']);

    $first = $this->withToken($token)
        ->getJson(attendedApiUrl($site['teamId']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.limit', 1)
        ->assertJsonPath('meta.returned', 1)
        ->assertJsonPath('meta.has_more', true);

    $this->withToken($token)
        ->getJson($first->json('links.next'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.has_more', false);
});

it('validates the required inclusive date range', function () {
    $site = createAttendedApiFixture('Validation Site');
    $token = createAttendedApiToken($site['teamId']);

    $this->withToken($token)
        ->getJson(attendedApiUrl($site['teamId'], '2032-11-30', '2032-11-01'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to');
});
