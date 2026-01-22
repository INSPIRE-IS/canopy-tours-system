<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'services';

    protected $casts = [
        'allow_internal_capacity_override' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'name',
        'tag',
        'duration',
        'pillbox_conditions',
        'capacity',
        'allow_internal_capacity_override',
        'created_by_id',
        'updated_by_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function seasons()
    {
        return $this->hasMany(Season::class, 'service_id');
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class, 'service_id');
    }

    public function activePromotions()
    {
        $today = now()->toDateString();

        return $this->hasMany(Promotion::class, 'service_id')
            ->where('booking_start_date', '<=', $today)
            ->where('booking_end_date', '>=', $today)
            ->where('internal_use_only', false);
    }


    public function servicefieldValues()
    {
        return $this->hasMany(ServiceFieldValue::class);
    }

    public function fields()
    {
        return $this->belongsToMany(ServiceField::class, 'service_field_values', 'service_id', 'field_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function file()
    {
        return $this->hasOne(File::class, 'table_row_id')->where('table_name', 'services');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
