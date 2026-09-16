<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingExtraPaymentAllocation extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait, Auditable;

    protected $fillable = [
        'booking_id',
        'booking_extra_id',
        'booking_payment_id',
        'amount',
        'allocation_type',
        'note',
        'team_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function booking_extra()
    {
        return $this->belongsTo(BookingExtra::class);
    }

    public function booking_payment()
    {
        return $this->belongsTo(BookingPayment::class);
    }
}
