<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LookupItem extends Model
{
    use HasFactory;

    public $table = 'lookupItems';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'rate',
        'multiplier',
        'effective_from',
        'effective_to',
        'is_default',
        'is_active',
        'team_id',
        'lookup_id',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
