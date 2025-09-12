<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPaxDiscount extends Model
{
    use HasFactory;

    public $table = 'booking_pax_discounts';

    protected $fillable = [
        'booking_item_id',
        'discount_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function booking_items()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}
