<?php

use App\Http\Controllers\Front\PublicBookingV2Controller;
use App\Models\Service;
use App\Models\Team;
use App\Services\Calendar\PublicServiceAvailability;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

function publicAvailabilityService(): Service
{
    $service = new Service([
        'name' => 'Canopy Tour',
        'is_online_bookable' => true,
    ]);
    $service->id = 7;
    $service->setRelation('team', new Team([
        'name' => 'Karkloof Canopy Tour',
        'is_published' => true,
    ]));

    return $service;
}

function publicAvailabilityRequest(array $query): Request
{
    return Request::create('/front/v2/services/7/availability', 'GET', $query);
}

it('rejects an availability range whose end precedes its start', function () {
    $request = publicAvailabilityRequest([
        'start_date' => '2026-12-31',
        'end_date' => '2026-12-30',
        'adults' => 2,
        'children' => 0,
    ]);

    expect(fn () => app(PublicBookingV2Controller::class)->availability(
        $request,
        publicAvailabilityService(),
        app(PublicServiceAvailability::class)
    ))->toThrow(ValidationException::class);
});

it('rejects availability requests longer than 63 inclusive days', function () {
    $response = app(PublicBookingV2Controller::class)->availability(
        publicAvailabilityRequest([
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-03',
            'adults' => 2,
            'children' => 0,
        ]),
        publicAvailabilityService(),
        app(PublicServiceAvailability::class)
    );

    expect($response->status())->toBe(422)
        ->and($response->getData(true))->toBe([
            'message' => 'Availability can be requested for a maximum of 63 days at a time.',
        ]);
});
