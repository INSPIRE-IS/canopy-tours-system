<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WholesalerRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'wholesaler_id',
        'season_id',
        'price_adult',
        'price_child',
        'price_guide',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function wholesaler()
    {
        return $this->belongsTo(Wholesaler::class);
    }
}
