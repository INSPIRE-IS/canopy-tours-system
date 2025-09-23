<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingDiscountItem extends Model
{
    use HasFactory;

    public $table = 'booking_discount_items';

    protected $fillable = [
        'booking_discount_id',
        'discount_id',
        'pax_type',
        'count',
        'base_rate',
        'final_rate',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function booking_discount()
    {
        return $this->belongsTo(BookingDiscount::class, 'booking_discount_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}
