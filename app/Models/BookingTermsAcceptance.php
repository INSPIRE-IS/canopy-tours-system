<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingTermsAcceptance extends Model
{
    use HasFactory, MultiTenantModelTrait;

    protected $fillable = [
        'booking_id',
        'team_id',
        'accepted_at',
        'wording_snapshot',
        'wording_hash',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
