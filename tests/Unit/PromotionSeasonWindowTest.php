<?php

use App\Support\PromotionSeasonWindow;

$seasons = [
    ['id' => 10, 'start_date' => '2030-07-01 00:00:00', 'end_date' => '2030-12-31 00:00:00', 'internal_only' => false],
    ['id' => 11, 'start_date' => '2031-01-02 00:00:00', 'end_date' => '2032-12-31 00:00:00', 'internal_only' => false],
    ['id' => 12, 'start_date' => '2030-01-01 00:00:00', 'end_date' => '2034-12-31 00:00:00', 'internal_only' => true],
];

it('allows service-level promotion endpoints in different public seasons', function () use ($seasons) {
    $errors = PromotionSeasonWindow::validationErrors(
        $seasons,
        'varied_child',
        null,
        '2030-10-01',
        '2032-12-31',
        false
    );

    expect($errors)->toBe([]);
});

it('accepts dates exactly on serialized season boundaries', function () use ($seasons) {
    $errors = PromotionSeasonWindow::validationErrors(
        $seasons,
        'percent',
        null,
        '2031-01-02',
        '2032-12-31',
        false
    );

    expect($errors)->toBe([]);
});

it('does not use an internal-only season for a public promotion', function () use ($seasons) {
    $errors = PromotionSeasonWindow::validationErrors(
        $seasons,
        'percent',
        null,
        '2030-02-01',
        '2032-12-31',
        false
    );

    expect($errors)->toHaveKey('activity_start_date');
});

it('allows internal promotions to use internal seasons', function () use ($seasons) {
    $errors = PromotionSeasonWindow::validationErrors(
        $seasons,
        'percent',
        null,
        '2030-02-01',
        '2034-12-31',
        true
    );

    expect($errors)->toBe([]);
});

it('keeps time-slot promotions within their selected season', function () use ($seasons) {
    $errors = PromotionSeasonWindow::validationErrors(
        $seasons,
        'time_slot',
        10,
        '2030-10-01',
        '2032-12-31',
        false
    );

    expect($errors)->toHaveKey('activity_start_date');
});
