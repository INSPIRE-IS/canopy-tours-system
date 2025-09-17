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

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'position',
        'notes',
        'is_indemnity',
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

    public function booking_items()
    {
        return $this->belongsTo(BookingItem::class, 'booking_item_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
