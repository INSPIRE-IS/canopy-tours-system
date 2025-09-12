<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingQuestionnaire extends Model
{
    use HasFactory;


    public $table = 'booking_questionnaires';

    protected $fillable = [
        'booking_item_id',
        'customer_id',
        'guide_id',
        'safety_guide_id',
        'type_group_id',
        'signature',
        'birth_date',
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

    public function booking_items()
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
}
