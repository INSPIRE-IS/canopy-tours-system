<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingExtra extends Model
{
    use Auditable, HasFactory, MultiTenantModelTrait, SoftDeletes;

    public const TYPE_MEAL = 'meal';

    public const TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'service_id',
        'season_extra_id',
        'team_id',
        'type',
        'name',
        'description',
        'quantity',
        'unit_price',
        'total_amount',
        'exclude_from_franchise_fees',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'total_amount' => 'float',
        'exclude_from_franchise_fees' => 'boolean',
        'meta' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function booking_item()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function season_extra()
    {
        return $this->belongsTo(SeasonExtra::class)->withTrashed();
    }

    public function payment_allocations()
    {
        return $this->hasMany(BookingExtraPaymentAllocation::class);
    }
}
