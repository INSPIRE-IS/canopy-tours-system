<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingNote extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'booking_notes';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'type',
        'note',
        'created_by_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
