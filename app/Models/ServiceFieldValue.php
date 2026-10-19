<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceFieldValue extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'service_field_values';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'service_id',
        'field_id',
        'value',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function field()
    {
        return $this->belongsTo(ServiceField::class, 'field_id');
    }

    public function fieldIncludingTrashed()
    {
        return $this->belongsTo(ServiceField::class, 'field_id')->withTrashed();
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
