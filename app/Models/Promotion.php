<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    public $table = 'promotions';

    protected $fillable = [
        'name',
        'promo_code',
        'type',
        'value',
        'service_id',
        'start_date',
        'end_date',
        'valid_days',
        'internal_use_only',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'internal_use_only' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
