<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static array $auditRedactedFields = [
        'password',
        'remember_token',
        'config',
        'merchant_key',
        'passphrase',
        'secret',
        'token',
        'signature',
    ];

    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::audit('audit:created', $model, [
                'after' => self::auditAttributes($model->getAttributes()),
            ]);
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            self::audit('audit:updated', $model, [
                'before' => self::auditAttributes($model->getOriginal(), array_keys($changes)),
                'after' => self::auditAttributes($changes),
                'changed' => array_keys($changes),
            ]);
        });

        static::deleted(function (Model $model) {
            self::audit('audit:deleted', $model, [
                'before' => self::auditAttributes($model->getOriginal()),
            ]);
        });
    }

    protected static function audit($description, $model, array $properties = [])
    {
        AuditLog::create([
            'description'  => $description,
            'subject_id'   => $model->id ?? null,
            'subject_type' => get_class($model),
            'user_id'      => auth()->id() ?? null,
            'properties'   => $properties,
            'host'         => request()->ip() ?? null,
        ]);
    }

    protected static function auditAttributes(array $attributes, ?array $onlyKeys = null): array
    {
        if ($onlyKeys !== null) {
            $attributes = array_intersect_key($attributes, array_flip($onlyKeys));
        }

        foreach ($attributes as $key => $value) {
            if (self::shouldRedactAuditField((string) $key)) {
                $attributes[$key] = blank($value) ? null : '[redacted]';
            }
        }

        return $attributes;
    }

    protected static function shouldRedactAuditField(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::$auditRedactedFields as $field) {
            if (str_contains($key, $field)) {
                return true;
            }
        }

        return false;
    }
}
