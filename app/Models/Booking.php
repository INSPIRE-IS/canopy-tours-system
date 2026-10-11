<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'bookings';

    protected $dates = [
        'due_date',
        'created_at',
        'updated_at',
        'deleted_at',
        'payment_window_expired_at',
    ];

    protected $fillable = [
        'user_id',
        'session_id',
        'lead_name',
        'type_booking_id',
        'status_payment_id',
        'payment_window_expired_at',
        'is_sms',
        'is_corporate',
        'due_date',
        'tot_paid',
        'grand_tot_due',
        'reference',
        'customer_id',
        'updated_by_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected $casts = [
        'payment_window_expired_at' => 'datetime',
    ];

    public const TIME_SLOTS = [
        '1' => '08:00',
        '2' => '09:00',
        '3' => '10:00',
        '4' => '11:00',
        '5' => '12:00',
        '6' => '13:00',
        '7' => '14:00',
        '8' => '15:00',
        '9' => '16:00',
        '10' => '17:00',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function booking_items()
    {
        return $this->hasMany(BookingItem::class);
    }

    public function booking_extras()
    {
        return $this->hasMany(BookingExtra::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function type_booking()
    {
        return $this->belongsTo(TypeBooking::class, 'type_booking_id');
    }

    public function status_payment()
    {
        return $this->belongsTo(StatusPayment::class, 'status_payment_id');
    }

    public function getDueDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format(config('panel.date_format')) : null;
    }

    public function setDueDateAttribute($value)
    {
        $this->attributes['due_date'] = $value ? Carbon::createFromFormat(config('panel.date_format'), $value)->format('Y-m-d') : null;
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }



    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    // public function booking_staff()
    // {
    //     return $this->hasMany(BookingStaff::class);
    // }

    // public function booking_details()
    // {
    //     return $this->hasMany(BookingDetail::class);
    // }

    // public function customer_manifests()
    // {
    //     return $this->hasMany(CustomerManifest::class);
    // }

    // public function booking_fees()
    // {
    //     return $this->hasMany(BookingFee::class);
    // }

    public function booking_payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    public function booking_discounts()
    {
        return $this->hasMany(BookingDiscount::class, 'booking_id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'booking_id');
    }

    // public function booking_inventory()
    // {
    //     return $this->hasOne(BookingInventory::class);
    // }

    // public function booking_infos()
    // {
    //     return $this->hasMany(BookingInfo::class);
    // }

    // public function booking_questionnaire()
    // {
    //     return $this->hasOne(BookingQuestionnaire::class);
    // }
}
