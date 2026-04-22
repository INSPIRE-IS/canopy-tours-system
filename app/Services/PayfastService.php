<?php

namespace App\Services;

class PayfastService
{
    public function generateSignature(array $data, ?string $passphrase = null): string
    {
        // Outgoing signature generation follows PayFast's "submit" guidance:
        // - exclude blank values
        // - sort keys alphabetically
        $data = $this->filterData($data, filterEmpty: true);
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

    public function verifySignature(array $data, string $signature, ?string $passphrase = null, bool $isItn = false): bool
    {
        // PayFast ITN (notify) signatures are generated using the *original* POST order
        // and include keys that may have empty-string values.
        // Our logs confirmed PayFast's signature matches only when we keep order + empties.
        if ($isItn) {
            $generated = $this->generateItnSignature($data, $passphrase);
        } else {
            $generated = $this->generateSignature($data, $passphrase);
        }

        return hash_equals($generated, $signature);
    }

    private function generateItnSignature(array $data, ?string $passphrase = null): string
    {
        // Preserve the incoming array order and DO NOT filter out empty strings.
        // Only remove the signature itself.
        unset($data['signature']);

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

    private function filterData(array $data, bool $filterEmpty = true): array
    {
        unset($data['signature']);

        if (! $filterEmpty) {
            return $data;
        }

        return array_filter($data, fn ($value) => $value !== null && $value !== '');
    }
}
