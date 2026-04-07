<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;

class Lookup extends Model
{
    use HasFactory, Auditable, MultiTenantModelTrait;

    public $table = 'lookups';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'team_id',
    ];
}
