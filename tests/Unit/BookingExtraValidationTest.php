<?php

use App\Http\Controllers\Admin\BookingsController;
use Tests\TestCase;

uses(TestCase::class);

function bookingExtraRules(): array
{
    return (new ReflectionMethod(BookingsController::class, 'bookingExtraValidationRules'))
        ->invoke(new BookingsController);
}

it('allows no extras and valid whole-number extras', function () {
    expect(validator(['booking_extras' => []], bookingExtraRules())->passes())->toBeTrue();

    $extras = [
        ['name' => 'Braai', 'quantity' => 12, 'unit_price' => 100],
        ['name' => 'Accommodation', 'quantity' => 2, 'unit_price' => 250.50],
    ];

    expect(validator(['booking_extras' => $extras], bookingExtraRules())->passes())->toBeTrue();
});

it('rejects incomplete or invalid extras', function (array $extra, string $field) {
    $errors = validator(['booking_extras' => [$extra]], bookingExtraRules())->errors();

    expect($errors->has("booking_extras.0.{$field}"))->toBeTrue();
})->with([
    'missing item' => [['quantity' => 1, 'unit_price' => 100], 'name'],
    'item too long' => [['name' => str_repeat('A', 31), 'quantity' => 1, 'unit_price' => 100], 'name'],
    'zero quantity' => [['name' => 'Meal', 'quantity' => 0, 'unit_price' => 100], 'quantity'],
    'fractional quantity' => [['name' => 'Meal', 'quantity' => 1.5, 'unit_price' => 100], 'quantity'],
    'missing amount' => [['name' => 'Meal', 'quantity' => 1], 'unit_price'],
    'zero amount' => [['name' => 'Meal', 'quantity' => 1, 'unit_price' => 0], 'unit_price'],
    'too many cents' => [['name' => 'Meal', 'quantity' => 1, 'unit_price' => 100.123], 'unit_price'],
]);

it('keeps the invoice item short and lets payment notes be composed separately', function () {
    $normalise = new ReflectionMethod(BookingsController::class, 'normaliseBookingExtras');
    $extras = $normalise->invoke(new BookingsController, [
        'booking_extras' => [['name' => 'Braai', 'quantity' => 12, 'unit_price' => 100]],
    ]);

    expect($extras)->toHaveCount(1)
        ->and($extras[0]['name'])->toBe('Braai')
        ->and($extras[0]['description'])->toBeNull()
        ->and($extras[0]['total_amount'])->toBe(1200.0);
});
