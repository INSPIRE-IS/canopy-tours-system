<?php

return [
    'merchant_id' => env('PAYFAST_MERCHANT_ID'),
    'merchant_key' => env('PAYFAST_MERCHANT_KEY'),
    'passphrase' => env('PAYFAST_PASSPHRASE'),
    'is_sandbox' => filter_var(env('PAYFAST_IS_SANDBOX', true), FILTER_VALIDATE_BOOL),
    'sandbox_url' => env('PAYFAST_SANDBOX_URL', 'https://sandbox.payfast.co.za/eng/process'),
    'live_url' => env('PAYFAST_LIVE_URL', 'https://www.payfast.co.za/eng/process'),
    'return_url' => env('PAYFAST_RETURN_URL'),
    'cancel_url' => env('PAYFAST_CANCEL_URL'),
    'notify_url' => env('PAYFAST_NOTIFY_URL'),
    'trust_return' => filter_var(env('PAYFAST_TRUST_RETURN', false), FILTER_VALIDATE_BOOL),
    'gateway_config_key' => env('PAYMENT_GATEWAY_CONFIG_KEY', env('APP_KEY')),
];
