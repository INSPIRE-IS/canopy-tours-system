<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'service_id',
        'applies_to',
        'percent_off',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
