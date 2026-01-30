<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardingPassTemplate extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait;

    public $table = 'boarding_pass_templates';

    protected $fillable = [
        'team_id',
        'service_id',
        'season_id',
        'name',
        'location',
        'address',
        'footer',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function sections()
    {
        return $this->hasMany(BoardingPassSection::class)->orderBy('sort_order');
    }
}
