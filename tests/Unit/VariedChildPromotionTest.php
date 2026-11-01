<?php

use App\Support\VariedChildPromotion;

function pairedChildParams(string $type = 'percent', float $value = 50): array
{
    return [
        'min_adults' => 1,
        'child_discount_mode' => 'one_per_adult',
        'child_discount_type' => $type,
        'child_discount_value' => $value,
    ];
}

it('preserves legacy varied-child params as all-children percentage rules', function () {
    $params = VariedChildPromotion::normalizeParams([
        'min_adults' => '1',
        'child_discount_percent' => '50',
    ]);

    expect($params['child_discount_mode'])->toBe('all_children')
        ->and($params['child_discount_type'])->toBe('percent')
        ->and($params['child_discount_value'])->toBe(50.0);
});

it('matches one discounted child to each adult', function (int $adults, int $children, int $expected) {
    expect(VariedChildPromotion::discountedChildCount($adults, $children, pairedChildParams()))
        ->toBe($expected);
})->with([
    'one adult and one child' => [1, 1, 1],
    'two adults and one child' => [2, 1, 1],
    'one adult and two children' => [1, 2, 1],
    'two adults and three children' => [2, 3, 2],
    'three adults and two children' => [3, 2, 2],
]);

it('rejects paired rules without an eligible adult-child pair', function (int $adults, int $children) {
    expect(fn () => VariedChildPromotion::discountedChildCount($adults, $children, pairedChildParams()))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'child without adult' => [0, 1],
    'adult without child' => [1, 0],
]);

it('accepts fixed Rand paired-child rules', function () {
    $params = VariedChildPromotion::normalizeParams(pairedChildParams('fixed_value', 200));

    expect($params['child_discount_type'])->toBe('fixed_value')
        ->and($params['child_discount_value'])->toBe(200.0)
        ->and($params)->not->toHaveKey('child_discount_percent');
});

it('calculates the paired percentage child subtotal without discounting extra children', function () {
    $pricing = VariedChildPromotion::pricingBreakdown(795, 1, 2, pairedChildParams('percent', 50));

    expect($pricing)->toMatchArray([
        'rack_count' => 1,
        'discounted_count' => 1,
        'discounted_rate' => 397.5,
        'line_total' => 1192.5,
        'average_rate' => 596.25,
    ]);
});

it('calculates fixed Rand discount once for each paired child', function () {
    $pricing = VariedChildPromotion::pricingBreakdown(795, 2, 3, pairedChildParams('fixed_value', 200));

    expect($pricing)->toMatchArray([
        'rack_count' => 1,
        'discounted_count' => 2,
        'discounted_rate' => 595.0,
        'line_total' => 1985.0,
    ]);
});

it('keeps the legacy all-children threshold behaviour', function () {
    $params = [
        'min_adults' => 2,
        'child_discount_percent' => 50,
    ];

    expect(VariedChildPromotion::pricingBreakdown(795, 1, 2, $params)['line_total'])->toBe(1590.0)
        ->and(VariedChildPromotion::pricingBreakdown(795, 2, 2, $params)['line_total'])->toBe(795.0);
});

it('caps a fixed Rand child discount at the child rate', function () {
    $pricing = VariedChildPromotion::pricingBreakdown(795, 1, 1, pairedChildParams('fixed_value', 1000));

    expect($pricing['discounted_rate'])->toBe(0.0)
        ->and($pricing['line_total'])->toBe(0.0);
});

it('rejects percentage values above 100', function () {
    expect(fn () => VariedChildPromotion::normalizeParams(pairedChildParams('percent', 101)))
        ->toThrow(InvalidArgumentException::class);
});
