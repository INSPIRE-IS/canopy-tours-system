<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeLocation extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'type_locations';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function bookingInfo()
    {
        return $this->hasMany(BookingInfo::class);
    }
}
