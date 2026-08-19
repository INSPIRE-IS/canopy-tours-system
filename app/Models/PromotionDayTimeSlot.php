<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionDayTimeSlot extends Model
{
    use HasFactory, Auditable;

    public $table = 'promotion_day_time_slots';

    protected $fillable = [
        'promotion_id',
        'day_id',
        'time_slot_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function day()
    {
        return $this->belongsTo(TypeDay::class);
    }

    public function time_slot()
    {
        return $this->belongsTo(InventoryTimeSlot::class);
    }
}
