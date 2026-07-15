<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPriceLine extends Model
{
    use HasFactory, MultiTenantModelTrait, SoftDeletes, Auditable;

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'team_id',
        'activity_date',
        'activity_time',
        'source_type',
        'source_id',
        'pax_type',
        'count',
        'base_rate',
        'final_rate',
        'line_total',
        'label',
        'meta',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'count' => 'integer',
        'base_rate' => 'decimal:2',
        'final_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function booking_item()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }
}
