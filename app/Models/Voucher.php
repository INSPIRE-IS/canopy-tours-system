<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'vouchers';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'voucher_code',
        'amount',
        'note',
        'expire_date',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
        'service_id',
        'season_id',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    // service
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    // season
    public function season()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }
}
