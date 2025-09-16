<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceTemplate extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait;

    public $table = 'invoice_templates';

    protected $fillable = [
        'name',
        'type',
        'content',
        'is_default',
        'team_id',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
