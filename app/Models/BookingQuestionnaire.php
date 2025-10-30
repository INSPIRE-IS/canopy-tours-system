<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingQuestionnaire extends Model
{
    use HasFactory;


    public $table = 'booking_questionnaires';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_medical' => 'boolean',
        'is_fit' => 'boolean',
        'is_minor' => 'boolean',
        'recieve_invite' => 'boolean',
        'is_agree' => 'boolean',
    ];

    protected $fillable = [
        'booking_item_id',
        'customer_id',
        'guide_id',
        'safety_guide_id',
        'type_group_id',
        'signature',
        'age',
        'type_referral',
        'country_id',
        'province_id',
        'is_medical',
        'medical_detail',
        'recieve_invite',
        'pully_number',
        'helmet_size',
        'gender_id',
        'is_fit',
        'is_minor',
        'is_agree',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function booking_item()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function guide()
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function safety_guide()
    {
        return $this->belongsTo(User::class, 'safety_guide_id');
    }

    public function type_group()
    {
        return $this->belongsTo(TypeGroup::class, 'type_group_id');
    }

    public function gender()
    {
        return $this->belongsTo(TypeGender::class, 'gender_id');
    }

    public function continent()
    {
        return $this->belongsTo(Continent::class, 'continent_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function province()
    {
        return $this->belongsTo(State::class, 'province_id');
    }

    public function is_medical()
    {
        return $this->where('medical_id', 1)->exists();
    }
}
