<?php

namespace App\Services;

class PayfastService
{
    public function generateSignature(array $data, ?string $passphrase = null): string
    {
        $data = $this->filterData($data);
        ksort($data);

        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = $key . '=' . urlencode(trim((string) $value));
        }

        $signatureString = implode('&', $pairs);
        if ($passphrase !== null && $passphrase !== '') {
            $signatureString .= '&passphrase=' . urlencode($passphrase);
        }

        return md5($signatureString);
    }

    public function verifySignature(array $data, string $signature, ?string $passphrase = null): bool
    {
        $generated = $this->generateSignature($data, $passphrase);

        return hash_equals($generated, $signature);
    }

    private function filterData(array $data): array
    {
        unset($data['signature']);

        return array_filter(
            $data,
            fn($value) => $value !== null && $value !== ''
        );
    }
}
