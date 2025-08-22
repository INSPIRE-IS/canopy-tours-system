<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;
    public $table = 'discounts';

    protected $fillable = [
        'name',
        'applies_to',
        'percent_off',
    ];

    public function services()
    {
        return $this->belongsToMany(Service::class, 'discount_services');
    }
}
