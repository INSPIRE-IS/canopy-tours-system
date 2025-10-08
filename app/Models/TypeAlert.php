<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeAlert extends Model
{
    use HasFactory;

    protected $table = 'type_alerts';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function alerts()
    {
        return $this->belongsToMany(Alert::class, 'alerts', 'type_alert_id', 'alert_id');
    }
}
