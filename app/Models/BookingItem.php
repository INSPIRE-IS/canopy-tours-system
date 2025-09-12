<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingItem extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'booking_items';

    protected $fillable = [
        'booking_id',
        'service_id',
        'pax_adults',
        'pax_children',
        'pax_guides',
        'price_adult',
        'price_child',
        'price_guide',
        'discount_id',
        'inventory_id',
        'season_id',
        'created_at',
        'updated_at',
        'deleted_at',
        // 'team_id',
    ];

    // protected function serializeDate(DateTimeInterface $date)
    // {
    //     return $date->format('Y-m-d H:i:s');
    // }
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // public function customers()
    // {
    //     //return $this->belongsToMany(Customer::class);
    //     return $this->belongsToMany(Customer::class)
    //         ->withPivot('team_id', 'is_lead')
    //         ->withTimestamps();
    // }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'booking_items_customer', 'booking_item_id', 'customer_id')
            ->withPivot(['team_id', 'is_lead']);
    }

    public function booking_details()
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function booking_fees()
    {
        return $this->hasMany(BookingFee::class);
    }

    // public function booking_payments()
    // {
    //     return $this->hasMany(BookingPayment::class);
    // }

    public function booking_inventory()
    {
        return $this->hasOne(BookingInventory::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function season()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function customer_manifests()
    {
        return $this->hasMany(CustomerManifest::class);
    }

    public function booking_staff()
    {
        return $this->hasMany(BookingStaff::class);
    }

    public function booking_infos()
    {
        return $this->hasMany(BookingInfo::class);
    }

    public function booking_questionnaire()
    {
        return $this->hasOne(BookingQuestionnaire::class);
    }
}
