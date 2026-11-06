<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FranchiseFeeRatePeriod extends Model
{
    use Auditable, HasFactory, MultiTenantModelTrait, SoftDeletes;

    protected $fillable = [
        'team_id',
        'royalty_percent',
        'marketing_percent',
        'effective_from',
        'effective_to',
        'is_active',
        'note',
    ];

    protected $casts = [
        'royalty_percent' => 'decimal:2',
        'marketing_percent' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
