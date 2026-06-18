<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPayment extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'booking_payments';

    protected $dates = [
        'payment_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'booking_id',
        'type_pay_option_id',
        'type_payment_id',
        'gateway_id',
        'status_payment_id',
        'payment_date',
        'ex_vat_amount',
        'lookup_item_id',
        'vat',
        'vat_amount',
        'additional_amount',
        'short_payment_note',
        'additional_payment_note',
        'voucher_code',
        'redeemer_name',
        'note',
        'updated_by_id',
        'reference',
        'meta',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function type_pay_option()
    {
        return $this->belongsTo(TypePayOption::class, 'type_pay_option_id');
    }

    public function type_payment()
    {
        return $this->belongsTo(TypePayment::class, 'type_payment_id');
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class, 'gateway_id');
    }

    public function status_payment()
    {
        return $this->belongsTo(StatusPayment::class, 'status_payment_id');
    }

    public function getPaymentDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setPaymentDateAttribute($value)
    {
        $this->attributes['payment_date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
