<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WholesalerContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wholesaler_contacts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'type',
        'wholesaler_id',
    ];

    public function wholesaler()
    {
        return $this->belongsTo(Wholesaler::class, 'wholesaler_id');
    }
}
