<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory, MultiTenantModelTrait;
    public $table = 'discounts';

    protected $fillable = [
        'name',
        'applies_to',
        'percent_off',
        'team_id',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'discount_services');
    }
}
