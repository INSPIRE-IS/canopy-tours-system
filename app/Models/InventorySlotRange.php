<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventorySlotRange extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait, Auditable;

    public $table = 'inventory_slot_ranges';

    protected $fillable = [
        'inventory_id',
        'time_slot_id',
        'start_date',
        'end_date',
        'internal_only',
        'capacity_override',
        'note',
        'created_by_id',
        'updated_by_id',
        'team_id',
    ];

    protected $casts = [
        'internal_only' => 'boolean',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function time_slot()
    {
        return $this->belongsTo(InventoryTimeSlot::class, 'time_slot_id');
    }
}
