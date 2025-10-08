<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesalerRate extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait;
    public $table = 'wholesaler_rates';

    protected $attributes = [
        'price_guide' => 0.00,
    ];

    protected $fillable = [
        'wholesaler_id',
        'service_id',
        'season_id',
        'price_adult',
        'price_child',
        'price_guide',
        'bill_back',
        'team_id',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function wholesaler()
    {
        return $this->belongsTo(Wholesaler::class);
    }
}
