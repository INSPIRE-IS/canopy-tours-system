<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WholesalerRate extends Model
{
    use HasFactory;
    public $table = 'wholesaler_rates';

    protected $fillable = [
        'wholesaler_id',
        'service_id',
        'season_id',
        'price_adult',
        'price_child',
        'price_guide',
        'bill_back',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function wholesaler()
    {
        return $this->belongsTo(Wholesaler::class);
    }
}
