<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Season;

class Promotion extends Model
{
    use HasFactory, MultiTenantModelTrait;

    public $table = 'promotions';

    protected $fillable = [
        'name',
        'promo_code',
        'type',
        'value',
        'params',
        'service_id',
        'season_id',
        'booking_start_date',
        'booking_end_date',
        'activity_start_date',
        'activity_end_date',
        'internal_use_only',
        'team_id',
    ];
    protected $appends = ['status', 'is_valid'];
    protected $casts = [
        'booking_start_date' => 'date',
        'booking_end_date' => 'date',
        'activity_start_date' => 'date',
        'activity_end_date' => 'date',
        'internal_use_only' => 'boolean',
        'params' => 'json',
        'is_valid' => 'boolean',
    ];


    public function getStatusAttribute()
    {
        $today = Carbon::today();

        if ($today->lt($this->booking_start_date)) {
            return 'upcoming';
        }

        if ($today->gt($this->booking_end_date)) {
            return 'expired';
        }

        return 'active';
    }

    // public function getIsValidAttribute()
    // {
    //     $today = Carbon::today();

    //     return $today->between($this->booking_start_date, $this->booking_end_date)
    //         && now()->between($this->activity_start_date, $this->activity_end_date);
    // }

    public function getIsValidAttribute()
    {
        return $this->attributes['is_valid'] ?? false;
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }


    public function promo_day_time_slots()
    {
        return $this->hasMany(PromotionDayTimeSlot::class);
    }

    public function validForDates($bookingDate, $activityDate, $season)
    {
        $today = now()->toDateString();

        // Booking window is mandatory
        if ($today < $this->booking_start_date || $today > $this->booking_end_date) {
            return false;
        }

        // Default activity window = season dates if not set
        $activityStart = $this->activity_start_date ?? $season->start_date;
        $activityEnd   = $this->activity_end_date   ?? $season->end_date;

        if ($activityDate < $activityStart || $activityDate > $activityEnd) {
            return false;
        }

        return true;
    }

    public function validateCode($slotDate, $dayNumber, $timeSlot, $activityId)
    {
        $today = now()->toDateString();

        // 1. Booking window must include today
        if ($today < $this->booking_start_date->toDateString() || $today > $this->booking_end_date->toDateString()) {
            return ['status' => false, 'message' => 'period in valid'];
        }

        // 2. Activity window must include the slot date
        $activityStart = $this->activity_start_date;
        $activityEnd   = $this->activity_end_date;

        if ($slotDate < $activityStart->toDateString() || $slotDate > $activityEnd->toDateString()) {
            return ['status' => false, 'message' => 'activity in valid'];
        }

        $season = Season::find($activityId);
        $serviceId = $season?->service_id ?? $activityId;

        // 3. Service must match
        if ($this->service_id != $serviceId) {
            return ['status' => false, 'message' => 'service in valid'];
        }

        if ($this->season_id && $season && $this->season_id != $season->id) {
            return ['status' => false, 'message' => 'season in valid'];
        }

        // 4. promo day time slot must match
        if ($this->promo_day_time_slots->isNotEmpty()) {
            $daySlots = $this->promo_day_time_slots->where('day_id', $dayNumber);
            $hasDayOnly = $daySlots->contains(function ($slot) {
                return $slot->time_slot_id == null;
            });
            $valid = $hasDayOnly ? true : ($timeSlot ? $daySlots->where('time_slot_id', $timeSlot->id)->first() : null);
            logger()->info('day time slot valid ', [$valid]);
            if (!$valid) {
                return ['status' => false, 'promo' => 'day time slot in valid'];
            }
        } else {
            logger()->info('not checking promo day time slot');
        }

        return ['status' => true, 'message' => 'valid'];
    }
}
