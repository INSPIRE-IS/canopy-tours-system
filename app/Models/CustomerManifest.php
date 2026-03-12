<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerManifest extends Model
{
    use SoftDeletes, MultiTenantModelTrait, Auditable, HasFactory;

    public $table = 'customer_manifests';

    public const ATTENDANCE_STATUSES = [
        'no_show',
        'arrived',
        'cancelled',
    ];

    public const ATTENDANCE_DEFAULT = 'no_show';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_feedback' => 'boolean',
        'is_indemnity' => 'boolean',
    ];

    protected $fillable = [
        'position',
        'notes',
        'is_indemnity',
        'is_feedback',
        'attendance_status',
        'customer_id',
        'booking_item_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
        'type',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function booking_item()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
