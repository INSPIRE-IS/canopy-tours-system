<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingDiscount extends Model
{
    use HasFactory;

    protected $table = 'booking_discounts';

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'final_price_adult',
        'final_price_child',
        'final_price_guide',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function booking_item()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function booking_discount_items()
    {
        return $this->hasMany(BookingDiscountItem::class);
    }
}
