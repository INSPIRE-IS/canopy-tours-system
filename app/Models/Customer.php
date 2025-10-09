<?php

namespace App\Models;

use App\Traits\Auditable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes, Auditable, HasFactory;

    public $table = 'customers';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'full_name',
        'email_address',
        'contact_number',
        'nationality_id',
        'age',
        'parent_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function parent()
    {
        return $this->belongsTo(Customer::class, 'parent_id');
    }

    public function booking()
    {
        return $this->belongsToMany(Booking::class);
    }

    public function manifest()
    {
        return $this->hasMany(CustomerManifest::class);
    }

    public function booking_questionnaire()
    {
        return $this->hasOne(BookingQuestionnaire::class);
    }

    public function bookingItems()
    {
        return $this->belongsToMany(BookingItem::class, 'booking_items_customer')
            ->withPivot(['team_id', 'is_lead'])
            ->withTimestamps();
    }

    public function booking_discount_items()
    {
        return $this->belongsToMany(BookingDiscountItem::class, 'customer_discounts');
    }
}
