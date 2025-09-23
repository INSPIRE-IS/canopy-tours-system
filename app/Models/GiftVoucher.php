<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftVoucher extends Model
{
    use HasFactory;

    protected $table = 'gift_vouchers';

    protected $fillable = [
        'full_name',
        'email_address',
        'contact_number',
        'user_id',
        'session_id',
        'voucher_code',
        'grand_tot_due',
        'note',
        'expire_date',
        'status_payment_id',
        'paid_date',
        'redeemed',
        'redeemed_date',
        // 'booking_id',
        // 'team_id',
        // 'service_id',
        // 'season_id',
    ];

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

    public function statusPayment()
    {
        return $this->belongsTo(StatusPayment::class);
    }

    public function gift_voucher_items()
    {
        return $this->hasMany(GiftVoucherItem::class);
    }
}
