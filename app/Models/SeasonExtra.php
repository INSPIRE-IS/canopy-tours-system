<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeasonExtra extends Model
{
    use Auditable, HasFactory, MultiTenantModelTrait, SoftDeletes;

    protected $fillable = [
        'season_id',
        'team_id',
        'name',
        'description',
        'unit_price',
        'is_per_guest',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'float',
        'is_per_guest' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function booking_extras()
    {
        return $this->hasMany(BookingExtra::class);
    }
}
