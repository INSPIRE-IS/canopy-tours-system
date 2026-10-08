<?php

use App\Models\Inventory;
use App\Models\Season;
use App\Services\Calendar\PublicSeasonRangeResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

uses(Tests\TestCase::class);

function publicSeason(int $id, string $startDate, string $endDate, int $serviceId = 7): Season
{
    $season = new Season([
        'service_id' => $serviceId,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
    $season->id = $id;
    $season->setRelation('inventory', new Inventory());

    return $season;
}

it('clamps an ordinary request to one public season', function () {
    $segments = app(PublicSeasonRangeResolver::class)->resolve(
        new Collection([
            publicSeason(8, '2026-07-01', '2026-12-31'),
        ]),
        CarbonImmutable::parse('2026-09-10'),
        CarbonImmutable::parse('2026-09-12')
    );

    expect($segments)->toHaveCount(1)
        ->and($segments[0]['season']->id)->toBe(8)
        ->and($segments[0]['start_date']->toDateString())->toBe('2026-09-10')
        ->and($segments[0]['end_date']->toDateString())->toBe('2026-09-12');
});

it('resolves adjacent public seasons into separate requested segments', function () {
    $segments = app(PublicSeasonRangeResolver::class)->resolve(
        new Collection([
            publicSeason(8, '2026-07-01', '2026-12-31'),
            publicSeason(13, '2027-01-01', '2027-12-31'),
        ]),
        CarbonImmutable::parse('2026-12-28'),
        CarbonImmutable::parse('2027-01-03')
    );

    expect($segments)->toHaveCount(2)
        ->and($segments[0]['season']->id)->toBe(8)
        ->and($segments[0]['start_date']->toDateString())->toBe('2026-12-28')
        ->and($segments[0]['end_date']->toDateString())->toBe('2026-12-31')
        ->and($segments[1]['season']->id)->toBe(13)
        ->and($segments[1]['start_date']->toDateString())->toBe('2027-01-01')
        ->and($segments[1]['end_date']->toDateString())->toBe('2027-01-03');
});

it('preserves a gap between seasons instead of extending either season', function () {
    $segments = app(PublicSeasonRangeResolver::class)->resolve(
        new Collection([
            publicSeason(8, '2026-07-01', '2026-12-31'),
            publicSeason(13, '2027-01-02', '2027-12-31'),
        ]),
        CarbonImmutable::parse('2026-12-31'),
        CarbonImmutable::parse('2027-01-02')
    );

    expect($segments)->toHaveCount(2)
        ->and($segments[0]['end_date']->toDateString())->toBe('2026-12-31')
        ->and($segments[1]['start_date']->toDateString())->toBe('2027-01-02');
});

it('rejects overlapping public seasons for the same service', function () {
    app(PublicSeasonRangeResolver::class)->resolve(
        new Collection([
            publicSeason(8, '2026-07-01', '2026-12-31'),
            publicSeason(16, '2026-08-12', '2027-08-12'),
        ]),
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-09-01')
    );
})->throws(LogicException::class, 'Public seasons 8 and 16 overlap for service 7.');
