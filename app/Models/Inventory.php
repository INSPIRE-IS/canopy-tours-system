<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\InventorySlotRange;

class Inventory extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'inventories';

    protected $casts = [
        'allow_internal_override' => 'boolean',
        'allow_slot_cutoff_hours' => 'boolean',
        'allow_slot_funneling' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'service_id',
        'season_id',
        'slot_cutoff_hours',
        'slot_funneling',
        'allow_internal_override',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function season()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function getStartDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function getEndDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function day_slots()
    {
        return $this->belongsToMany(TypeDay::class);
    }

    public function inventory_type_days()
    {
        return $this->hasMany(InventoryTypeDay::class)->orderBy('type_day_id');
    }

    public function inventory_time_slots()
    {
        return $this->hasMany(InventoryTimeSlot::class, 'inventory_id')->orderBy('start_time');
    }

    public function inventory_slot_ranges()
    {
        return $this->hasMany(InventorySlotRange::class, 'inventory_id');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    protected static function booted()
    {
        static::deleting(function (Inventory $inventory) {
            if ($inventory->isForceDeleting()) {
                $inventory->inventory_time_slots()->withTrashed()->get()->each->forceDelete();
                $inventory->inventory_type_days()->withTrashed()->get()->each->forceDelete();
                $inventory->inventory_slot_ranges()->withTrashed()->get()->each->forceDelete();
                return;
            }

            $inventory->inventory_time_slots()->get()->each->delete();
            $inventory->inventory_type_days()->get()->each->delete();
            $inventory->inventory_slot_ranges()->get()->each->delete();
        });
    }
}
