<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingVoucherItem extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'booking_voucher_items';

    protected $casts = [
        'disabled' => 'boolean',
    ];

    protected $fillable = [
        'booking_voucher_id',
        'name',
        'helper',
        'description',
        'disabled',
        'type',
        'format',
    ];

    public function bookingVoucher()
    {
        return $this->belongsTo(BookingVoucher::class, 'booking_voucher_id');
    }
}
