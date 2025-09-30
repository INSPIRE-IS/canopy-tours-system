<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingFee extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'booking_fees';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'booking_item_id',
        'booking_detail_id',
        'tot_adults',
        'tot_children',
        'tot_guides',
        'is_voucher',
        'voucher_code',
        'invoice_note',
        'is_email_reminder',
        'booking_note',
        'tot_amount',
        'tot_paid',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function booking_items()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function booking_detail()
    {
        return $this->belongsTo(BookingDetail::class, 'booking_detail_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
