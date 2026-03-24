<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Lookup;

class LookupItem extends Model
{
    use HasFactory;

    public $table = 'lookup_items';

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
        $defaults = [
            'enable_pickup_dropoff' => true,
            'auto_send_invoice_on_booking' => true,
            'auto_send_quote_on_booking' => false,
            'enable_assign_staff' => true,
            'show_send_invoice_checkbox' => false,
            'show_send_quote_checkbox' => false,
            'show_booking_information' => true,
        ];

        $lookup = Lookup::where('slug', 'settings')->first();
        if (!$lookup) {
            return $defaults;
        }

        $items = static::defaultsForTeam($teamId, $lookup->id);
        $bySlug = $items->keyBy('slug');

        $settings = [];
        foreach ($defaults as $slug => $default) {
            $value = $bySlug->get($slug)?->multiplier;
            $settings[$slug] = $value === null ? $default : (bool) $value;
        }

        return $settings;
    }
}
