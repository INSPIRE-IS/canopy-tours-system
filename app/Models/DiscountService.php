<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountService extends Model
{
    use HasFactory;

    public $table = 'discount_services';

    protected $fillable = [
        'discount_id',
        'service_id',
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
