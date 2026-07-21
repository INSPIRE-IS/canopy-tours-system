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
        if (!$value) {
            return $value;
        }

        $path = (string) $value;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsedPath = parse_url($path, PHP_URL_PATH) ?: '';
            $storagePos = strrpos($parsedPath, '/storage/');
            if ($storagePos !== false) {
                return asset(ltrim(substr($parsedPath, $storagePos + 1), '/'));
            }

            return $path;
        }

        $path = ltrim($path, '/');
        if (str_contains($path, '/storage/')) {
            $storagePos = strrpos($path, '/storage/');
            return asset(ltrim(substr($path, $storagePos + 1), '/'));
        }

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'images/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }
}
