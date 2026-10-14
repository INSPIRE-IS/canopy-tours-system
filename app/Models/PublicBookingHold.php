<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicBookingHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'session_id',
        'team_id',
        'service_id',
        'season_id',
        'inventory_id',
        'inventory_time_slot_id',
        'booking_id',
        'booking_date',
        'adults',
        'children',
        'expires_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'booking_date' => 'date:Y-m-d',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now());
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function inventoryTimeSlot()
    {
        return $this->belongsTo(InventoryTimeSlot::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
