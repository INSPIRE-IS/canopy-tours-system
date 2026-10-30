<?php

namespace App\Support;

final class PayfastLogSanitizer
{
    private const SENSITIVE_KEYS = [
        'merchant_id',
        'merchant_key',
        'passphrase',
        'signature',
    ];

    public static function sanitize(array $payload): array
    {
        return array_diff_key($payload, array_flip(self::SENSITIVE_KEYS));
    }
}
