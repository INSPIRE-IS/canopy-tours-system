<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingInfo extends Model
{
    use HasFactory;

    public $table = 'booking_infos';

    protected $fillable = [
        'booking_item_id',
        'type_location_id',
        'location',
        'time',
        'meridiem',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function booking_items()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function typeLocation()
    {
        return $this->belongsTo(TypeLocation::class, 'type_location_id');
    }
}
