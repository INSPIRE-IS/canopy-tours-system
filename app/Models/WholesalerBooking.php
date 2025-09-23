<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesalerBooking extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'wholesaler_bookings';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'consultant_name',
        'consultant_email',
        'consultant_mobile',
        'booking_id',
        'wholesaler_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function wholesaler()
    {
        return $this->belongsTo(Wholesaler::class, 'wholesaler_id');
    }
}
