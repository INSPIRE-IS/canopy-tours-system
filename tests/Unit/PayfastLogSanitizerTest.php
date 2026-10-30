<?php

use App\Support\PayfastLogSanitizer;

it('removes gateway credentials and signatures while retaining reconciliation fields', function () {
    $sanitized = PayfastLogSanitizer::sanitize([
        'merchant_id' => 'merchant-id',
        'merchant_key' => 'merchant-key',
        'passphrase' => 'secret-passphrase',
        'signature' => 'signed-value',
        'm_payment_id' => 'booking-123',
        'pf_payment_id' => 'payfast-456',
        'payment_status' => 'COMPLETE',
        'amount_gross' => '100.00',
    ]);

    expect($sanitized)
        ->not->toHaveKeys(['merchant_id', 'merchant_key', 'passphrase', 'signature'])
        ->toMatchArray([
            'm_payment_id' => 'booking-123',
            'pf_payment_id' => 'payfast-456',
            'payment_status' => 'COMPLETE',
            'amount_gross' => '100.00',
        ]);
});
