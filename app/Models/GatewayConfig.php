<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class GatewayConfig extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public $table = 'payment_gateway_configs';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'team_id',
        'gateway_id',
        'is_enabled',
        'is_sandbox',
        'config',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_sandbox' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function getConfigAttribute($value): array
    {
        if (empty($value)) {
            return [];
        }

        try {
            $decrypted = $this->gatewayConfigEncrypter()->decrypt($value);
            return is_array($decrypted) ? $decrypted : [];
        } catch (\Throwable $customException) {
            try {
                $legacy = Crypt::decrypt($value);
                return is_array($legacy) ? $legacy : [];
            } catch (DecryptException $legacyException) {
                Log::warning('Gateway config could not be decrypted', [
                    'gateway_config_id' => $this->id,
                    'gateway_id' => $this->gateway_id,
                    'team_id' => $this->team_id,
                    'message' => $legacyException->getMessage(),
                ]);

                return [];
            }
        }
    }

    public function setConfigAttribute($value): void
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        $this->attributes['config'] = $this->gatewayConfigEncrypter()->encrypt(is_array($value) ? $value : []);
    }

    private function gatewayConfigEncrypter(): Encrypter
    {
        $key = config('payfast.gateway_config_key') ?: config('app.key');
        $cipher = config('app.cipher', 'AES-256-CBC');

        if (is_string($key) && str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false && Encrypter::supported($decoded, $cipher)) {
                return new Encrypter($decoded, $cipher);
            }
        }

        if (is_string($key) && Encrypter::supported($key, $cipher)) {
            return new Encrypter($key, $cipher);
        }

        // Accept a stable passphrase-like env value and derive a valid AES-256 key.
        return new Encrypter(hash('sha256', (string) $key, true), $cipher);
    }
}
