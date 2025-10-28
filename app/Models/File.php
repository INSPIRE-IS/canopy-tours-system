<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait;

    public $table = 'files';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'team_id',
        'table_name',
        'table_row_id',
        'file_name',
        'file_type',
        'type_file_id',
        'booking_id',
        'path',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function site()
    {
        return $this->belongsTo(Team::class, 'site_id');
    }

    public function type_file()
    {
        return $this->belongsTo(TypeFile::class, 'type_file_id');
    }


    public function getPathAttribute($value)
    {
        // Ensure public disk
        return asset('storage/' . $value);
    }
}
