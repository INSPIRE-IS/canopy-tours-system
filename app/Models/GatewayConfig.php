<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatewayConfig extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'payment_gateway_configs';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'team_id',
        'gateway_id',
        'is_enabled',
        'is_sandbox',
        'config',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_sandbox' => 'boolean',
        'config' => 'encrypted:array',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }
}
