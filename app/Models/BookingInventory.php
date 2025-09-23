<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingInventory extends Model
{
    use SoftDeletes, HasFactory, Auditable;

    public $table = 'booking_inventories';

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [

        'booking_item_id',
        'booking_detail_id',
        'date',
        'tot_adults',
        'tot_children',
        'tot_guides',
        'pax',
        'inventory_type_day_id',
        'inventory_time_slot_id',
        'inventory_override_id',
        'team_id',
        'created_at',
        'updated_at',
        'deleted_at',
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

    public function booking_detail()
    {
        return $this->belongsTo(BookingDetail::class, 'booking_detail_id');
    }

    public function getDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDateAttribute($value)
    {
        $this->attributes['date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function inventory_type_day()
    {
        return $this->belongsTo(InventoryTypeDay::class, 'inventory_type_day_id');
    }

    public function inventory_time_slot()
    {
        return $this->belongsTo(InventoryTimeSlot::class, 'inventory_time_slot_id');
    }

    public function inventory_override()
    {
        return $this->belongsTo(InventoryOverride::class, 'inventory_override_id');
    }
}
