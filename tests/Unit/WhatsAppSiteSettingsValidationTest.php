<?php

use App\Http\Requests\StoreTeamRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

function validateWhatsAppSiteSettings(array $data)
{
    return Validator::make($data, (new StoreTeamRequest())->rules());
}

it('accepts enabled WhatsApp assistance with a South African international number and message', function () {
    $validator = validateWhatsAppSiteSettings([
        'name' => 'Karkloof',
        'whatsapp_enabled' => true,
        'whatsapp_number' => '+27762412888',
        'whatsapp_message' => 'Hi, I need assistance with an online booking.',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('requires a valid WhatsApp number and message when assistance is enabled', function (array $overrides, string $expectedField) {
    $validator = validateWhatsAppSiteSettings(array_merge([
        'name' => 'Karkloof',
        'whatsapp_enabled' => true,
        'whatsapp_number' => '+27762412888',
        'whatsapp_message' => 'Hi, I need assistance with an online booking.',
    ], $overrides));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has($expectedField))->toBeTrue();
})->with([
    'missing number' => [['whatsapp_number' => ''], 'whatsapp_number'],
    'local number' => [['whatsapp_number' => '0762412888'], 'whatsapp_number'],
    'number containing spaces' => [['whatsapp_number' => '+27 76 241 2888'], 'whatsapp_number'],
    'missing message' => [['whatsapp_message' => ''], 'whatsapp_message'],
]);

it('allows WhatsApp assistance details to remain empty while the feature is disabled', function () {
    $validator = validateWhatsAppSiteSettings([
        'name' => 'Karkloof',
        'whatsapp_enabled' => false,
        'whatsapp_number' => '',
        'whatsapp_message' => '',
    ]);

    expect($validator->passes())->toBeTrue();
});
