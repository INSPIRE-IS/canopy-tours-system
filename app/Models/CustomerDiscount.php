<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDiscount extends Model
{
    use HasFactory;

    public $table = 'customer_discounts';

    protected $fillable = [
        'id',
        'customer_id',
        'booking_discount_item_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function booking_discount_item()
    {
        return $this->belongsTo(BookingDiscountItem::class, 'booking_discount_item_id');
    }
}
