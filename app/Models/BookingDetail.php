<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingDetail extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'booking_details';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'booking_item_id',
        'service_id',
        'season_id',
        'referral_source_id',
        'is_wholesaler_booking',
        'is_needs_pickup',
        'is_web_booking',
        'is_videos_photos',
        'is_exclusive_resource_use',
        'is_override_capacity',
        'is_override_time_slots',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function booking_items()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function season()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
