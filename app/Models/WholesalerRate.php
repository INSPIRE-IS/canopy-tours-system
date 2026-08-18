<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesalerRate extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait, Auditable;
    public $table = 'wholesaler_rates';

    protected $attributes = [
        'price_guide' => 0.00,
        'is_prepaid_voucher' => false,
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'wholesaler_id',
        'service_id',
        'season_id',
        'price_adult',
        'price_child',
        'price_guide',
        'bill_back',
        'is_prepaid_voucher',
        'effective_from',
        'effective_to',
        'is_active',
        'note',
        'team_id',
    ];

    protected $casts = [
        'bill_back' => 'boolean',
        'is_prepaid_voucher' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
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
