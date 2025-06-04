<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CloseoutDayTimeSlot extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'closeout_day_time_slots';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'closeout_id',
        'day_id',
        'created_at',
        'time_slot_id',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function closeout()
    {
        return $this->belongsTo(Closeout::class, 'closeout_id');
    }

    public function day()
    {
        return $this->belongsTo(TypeDay::class, 'day_id');
    }

    public function time_slot()
    {
        return $this->belongsTo(TypeTimeSlot::class, 'time_slot_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
