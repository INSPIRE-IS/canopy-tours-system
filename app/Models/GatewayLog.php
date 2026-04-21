<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GatewayLog extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'payment_gateway_logs';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'event',
        'payment_status',
        'm_payment_id',
        'pf_payment_id',
        'amount_gross',
        'signature_valid',
        'ip_address',
        'payload',
        'message',
        'gateway_id',
        'team_id',
        'booking_id',
        'gift_voucher_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload' => 'encrypted:array',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
