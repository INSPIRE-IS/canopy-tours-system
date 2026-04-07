<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Lookup;
use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;

class LookupItem extends Model
{
    use HasFactory, Auditable, MultiTenantModelTrait;

    public $table = 'lookup_items';

    protected $casts = [
        'value' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'info',
        'slug',
        'rate',
        'multiplier',
        'value',
        'effective_from',
        'effective_to',
        'is_default',
        'is_active',
        'team_id',
        'lookup_id',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public static function defaultsForTeam(?int $teamId, ?int $lookupId = null): Collection
    {
        $query = static::query()
            ->where('is_default', true)
            ->where(function ($q) use ($teamId) {
                $q->whereNull('team_id');
                if ($teamId) {
                    $q->orWhere('team_id', $teamId);
                }
            });

        if ($lookupId) {
            $query->where('lookup_id', $lookupId);
        }

        $items = $query
            ->orderByRaw('team_id IS NULL')
            ->orderBy('id')
            ->get();

        return $items
            ->unique(function ($item) {
                return $item->slug ?: $item->id;
            })
            ->values();
    }

    public static function findDefaultForTeam(string $slug, ?int $teamId, ?int $lookupId = null): ?self
    {
        $query = static::query()
            ->where('slug', $slug)
            ->where('is_default', true)
            ->where(function ($q) use ($teamId) {
                $q->whereNull('team_id');
                if ($teamId) {
                    $q->orWhere('team_id', $teamId);
                }
            });

        if ($lookupId) {
            $query->where('lookup_id', $lookupId);
        }

        return $query
            ->orderByRaw('team_id IS NULL')
            ->first();
    }

    public static function settingsForTeam(?int $teamId): array
    {
        static::ensureSettingsForTeam($teamId);

        $lookup = Lookup::where('slug', 'settings')->first();
        if (!$lookup) {
            return [];
        }

        $items = static::defaultsForTeam($teamId, $lookup->id);
        return $items
            ->keyBy('slug')
            ->map(fn($item) => (bool) ($item->value ?? 0))
            ->all();
    }

    private static function settingsDefinitions(): array
    {
        return [
            [
                'slug' => 'enable_pickup_dropoff',
                'name' => 'Enable Pickup/Dropoff',
                'default' => false,
            ],
            [
                'slug' => 'auto_send_invoice_on_booking',
                'name' => 'Auto Send Invoice on Booking',
                'default' => false,
            ],
            [
                'slug' => 'auto_send_quote_on_booking',
                'name' => 'Auto Send Quote on Booking',
                'default' => false,
            ],
            [
                'slug' => 'enable_assign_staff',
                'name' => 'Enable Assign Staff',
                'default' => false,
            ],
            [
                'slug' => 'show_send_invoice_checkbox',
                'name' => 'Show Send Invoice Checkbox',
                'default' => false,
            ],
            [
                'slug' => 'show_send_quote_checkbox',
                'name' => 'Show Send Quote Checkbox',
                'default' => false,
            ],
            [
                'slug' => 'show_booking_information',
                'name' => 'Show Booking Information',
                'default' => false,
            ],
        ];
    }

    public static function ensureSettingsForTeam(?int $teamId): void
    {
        $lookup = Lookup::firstOrCreate(
            ['slug' => 'settings'],
            ['name' => 'Settings', 'team_id' => null]
        );

        $defaults = static::query()
            ->where('lookup_id', $lookup->id)
            ->where('is_default', true)
            ->whereNull('team_id')
            ->get();

        if ($defaults->isEmpty()) {
            $today = now()->toDateString();
            foreach (static::settingsDefinitions() as $definition) {
                static::create([
                    'lookup_id' => $lookup->id,
                    'slug' => $definition['slug'],
                    'name' => $definition['name'],
                    'info' => $definition['name'],
                    'rate' => null,
                    'multiplier' => null,
                    'value' => $definition['default'] ? 1 : 0,
                    'effective_from' => $today,
                    'effective_to' => null,
                    'is_default' => true,
                    'is_active' => true,
                    'team_id' => null,
                ]);
            }

            $defaults = static::query()
                ->where('lookup_id', $lookup->id)
                ->where('is_default', true)
                ->whereNull('team_id')
                ->get();
        }

        $defaults->each(function ($item) {
            if ($item->value === null) {
                $fallback = $item->multiplier;
                $item->value = 0;
                $item->multiplier = null;
                $item->save();
            }
        });

        if (!$teamId) {
            return;
        }

        static::query()
            ->where('lookup_id', $lookup->id)
            ->where('team_id', $teamId)
            ->get()
            ->each(function ($item) {
                if ($item->value === null) {
                    $fallback = $item->multiplier;
                    $item->value = 0;
                    $item->multiplier = null;
                    $item->save();
                }
            });

        foreach ($defaults as $default) {
            static::firstOrCreate(
                [
                    'lookup_id' => $lookup->id,
                    'slug' => $default->slug,
                    'team_id' => $teamId,
                ],
                [
                    'name' => $default->name ?? $default->slug,
                    'info' => $default->info ?? $default->name ?? $default->slug,
                    'rate' => $default->rate,
                    'multiplier' => null,
                    'value' => $default->value ?? 0,
                    'effective_from' => $default->effective_from ?? now()->toDateString(),
                    'effective_to' => $default->effective_to,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
