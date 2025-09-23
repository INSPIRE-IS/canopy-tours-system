<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftVoucherItem extends Model
{
    use HasFactory;

    protected $table = 'gift_voucher_items';

    protected $fillable = [
        'pax_adults',
        'pax_children',
        'price_adult',
        'price_child',
        'gift_voucher_id',
        'booking_id',
        'team_id',
        'service_id',
        'season_id',
    ];

    public function giftVoucher()
    {
        return $this->belongsTo(GiftVoucher::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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
}
