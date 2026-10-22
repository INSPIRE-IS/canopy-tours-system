<?php

use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use Tests\TestCase;

uses(TestCase::class);

function prepareInventoryFunnelingRequest(string $requestClass, array $payload): int
{
    $request = $requestClass::create('/admin/inventories', 'POST', $payload);
    $method = new ReflectionMethod($requestClass, 'prepareForValidation');
    $method->invoke($request);

    return (int) $request->input('slot_funneling');
}

it('normalizes a stale funnel value to zero when the setting is switched off', function (string $requestClass) {
    expect(prepareInventoryFunnelingRequest($requestClass, [
        'allow_slot_funneling' => false,
        'slot_funneling' => 3,
    ]))->toBe(0);
})->with([
    'store request' => StoreInventoryRequest::class,
    'update request' => UpdateInventoryRequest::class,
]);

it('preserves the configured max-open-slots value when funneling is enabled', function (string $requestClass) {
    expect(prepareInventoryFunnelingRequest($requestClass, [
        'allow_slot_funneling' => true,
        'slot_funneling' => 3,
    ]))->toBe(3);
})->with([
    'store request' => StoreInventoryRequest::class,
    'update request' => UpdateInventoryRequest::class,
]);
