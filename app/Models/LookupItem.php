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
        $query = static::withoutGlobalScope('team_id')
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
        $query = static::withoutGlobalScope('team_id')
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

        $lookup = static::settingsLookup();
        if (!$lookup) {
            return [];
        }

        $items = static::defaultsForTeam($teamId, $lookup->id);
        return $items
            ->keyBy('slug')
            ->map(fn($item) => (bool) ($item->value ?? 0))
            ->all();
    }

    public static function franchiseFeeSettingsForTeam(?int $teamId): array
    {
        static::ensureSettingsForTeam($teamId);

        $lookup = static::settingsLookup();
        if (!$lookup) {
            return [
                'franchise_royalty_percent' => 10.0,
                'franchise_marketing_percent' => 1.5,
            ];
        }

        $items = static::defaultsForTeam($teamId, $lookup->id)->keyBy('slug');

        return [
            'franchise_royalty_percent' => static::settingRate($items->get('franchise_royalty_percent'), 10.0),
            'franchise_marketing_percent' => static::settingRate($items->get('franchise_marketing_percent'), 1.5),
        ];
    }

    private static function settingsDefinitions(): array
    {
        return [
            [
                'slug' => 'email_sending_enabled',
                'name' => 'Enable Guest Email Sending',
                'info' => 'Master site switch for sending guest emails. If disabled, automatic and manual guest emails are blocked.',
                'default' => true,
            ],
            [
                'slug' => 'manual_email_sending_enabled',
                'name' => 'Enable Manual Email Sending',
                'info' => 'Allow staff to deliberately send guest emails such as payment links, invoices, check-in links, and guest messages.',
                'default' => true,
            ],
            [
                'slug' => 'enable_pickup_dropoff',
                'name' => 'Enable Pickup/Dropoff',
                'default' => false,
            ],
            [
                'slug' => 'auto_send_invoice_on_booking',
                'name' => 'Auto Send Invoice on Booking',
                'info' => 'Automatically send invoice and boarding pass emails when a booking is created or updated.',
                'default' => false,
            ],
            [
                'slug' => 'auto_send_quote_on_booking',
                'name' => 'Auto Send Quote on Booking',
                'info' => 'Automatically send quote emails when a booking is created or updated.',
                'default' => false,
            ],
            [
                'slug' => 'auto_send_payment_confirmation',
                'name' => 'Auto Send Payment Confirmation',
                'info' => 'Automatically send confirmation emails after frontend PayFast or simulated payment flows complete.',
                'default' => false,
            ],
            [
                'slug' => 'auto_send_questionnaire_confirmation',
                'name' => 'Show Guest Indemnity Email Option',
                'info' => 'Show the guest an optional checkbox to receive a signed indemnity PDF by email. The checkbox remains off by default.',
                'default' => false,
            ],
            [
                'slug' => 'force_adult_guardian_selection',
                'name' => 'Force Adult Guardian Selection',
                'info' => 'Require minor/child indemnities to choose a parent/guardian from the adults on the booking. If disabled, a free-text parent/guardian name is captured.',
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
                'default' => true,
            ],
            [
                'slug' => 'show_send_quote_checkbox',
                'name' => 'Show Send Quote Checkbox',
                'default' => true,
            ],
            [
                'slug' => 'show_booking_information',
                'name' => 'Show Booking Information',
                'default' => true,
            ],
            [
                'slug' => 'franchise_royalty_percent',
                'name' => 'Franchise Royalty Percent',
                'default' => null,
                'rate_default' => null,
                'setting_type' => 'rate',
            ],
            [
                'slug' => 'franchise_marketing_percent',
                'name' => 'Franchise Marketing Percent',
                'default' => null,
                'rate_default' => null,
                'setting_type' => 'rate',
            ],
        ];
    }

    public static function ensureSettingsForTeam(?int $teamId): void
    {
        $lookup = static::settingsLookup();

        $today = now()->toDateString();
        foreach (static::settingsDefinitions() as $definition) {
            // Global defaults must bypass the tenant creation hook, which assigns the signed-in user's team.
            static::withoutEvents(fn() => static::withoutGlobalScope('team_id')->firstOrCreate(
                [
                    'lookup_id' => $lookup->id,
                    'slug' => $definition['slug'],
                    'team_id' => null,
                ],
                [
                    'name' => $definition['name'],
                    'info' => $definition['info'] ?? $definition['name'],
                    'rate' => $definition['rate_default'] ?? null,
                    'multiplier' => null,
                    'value' => ($definition['setting_type'] ?? 'boolean') === 'rate'
                        ? null
                        : ($definition['default'] ? 1 : 0),
                    'effective_from' => $today,
                    'effective_to' => null,
                    'is_default' => true,
                    'is_active' => true,
                ]
            ));
        }

        $defaults = static::withoutGlobalScope('team_id')
            ->where('lookup_id', $lookup->id)
            ->where('is_default', true)
            ->whereNull('team_id')
            ->get();

        $defaults->each(function ($item) {
            if ($item->value === null && !static::isRateSetting($item->slug)) {
                $fallback = $item->multiplier;
                $item->value = 0;
                $item->multiplier = null;
                $item->save();
            }
        });

        if (!$teamId) {
            return;
        }

        static::withoutGlobalScope('team_id')
            ->where('lookup_id', $lookup->id)
            ->where('team_id', $teamId)
            ->get()
            ->each(function ($item) {
                if ($item->value === null && !static::isRateSetting($item->slug)) {
                    $fallback = $item->multiplier;
                    $item->value = 0;
                    $item->multiplier = null;
                    $item->save();
                }
            });

        foreach ($defaults as $default) {
            static::withoutGlobalScope('team_id')->firstOrCreate(
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
                    'value' => static::isRateSetting($default->slug) ? null : ($default->value ?? 0),
                    'effective_from' => $default->effective_from ?? now()->toDateString(),
                    'effective_to' => $default->effective_to,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    private static function settingsLookup(): Lookup
    {
        // Reuse the legacy settings container regardless of ownership; new containers are global.
        return Lookup::withoutEvents(fn() => Lookup::withoutGlobalScope('team_id')->firstOrCreate(
            ['slug' => 'settings'],
            [
                'name' => 'Settings',
                'team_id' => null,
            ]
        ));
    }

    private static function isRateSetting(?string $slug): bool
    {
        return in_array($slug, [
            'franchise_royalty_percent',
            'franchise_marketing_percent',
        ], true);
    }

    private static function settingRate(?self $item, float $fallback): float
    {
        if (!$item || $item->rate === null || $item->rate === '') {
            return $fallback;
        }

        return (float) $item->rate;
    }
}
