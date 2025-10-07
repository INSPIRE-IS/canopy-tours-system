<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingVoucher extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait, Auditable;

    public $table = 'booking_vouchers';

    protected $fillable = [
        'name',
        'service_id',
        'season_id',
        'team_id',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function season()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function items()
    {
        return $this->hasMany(BookingVoucherItem::class);
    }
}
