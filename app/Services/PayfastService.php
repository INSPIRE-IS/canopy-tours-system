<?php

namespace App\Services;

class PayfastService
{
    public function generateSignature(array $data, ?string $passphrase = null): string
    {
        return md5($this->generateSignatureString($data, $passphrase));
    }

    public function generateSignatureString(array $data, ?string $passphrase = null, bool $maskPassphrase = false): string
    {
        // PayFast checkout signatures use the exact submitted field order.
        // Alphabetical sorting is only for PayFast API calls, not /eng/process posts.
        $data = $this->filterData($data, filterEmpty: true);

        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = $key . '=' . urlencode(trim((string) $value));
        }

        $signatureString = implode('&', $pairs);
        if ($passphrase !== null && $passphrase !== '') {
            $signatureString .= '&passphrase=' . ($maskPassphrase
                ? '[set len=' . strlen($passphrase) . ']'
                : urlencode(trim($passphrase)));
        }

        return $signatureString;
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
            $signatureString .= '&passphrase=' . urlencode(trim($passphrase));
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
