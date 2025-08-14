<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPricing extends Model
{
    use HasFactory;

    protected $table = 'booking_pricing';

    protected $fillable = [
        'booking_id',
        'season_id',
        'wholesaler_rate_id',
        'discount_id',
        'promotion_id',
        'final_price_adult',
        'final_price_child',
        'final_price_guide',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function wholesalerRate()
    {
        return $this->belongsTo(WholesalerRate::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
