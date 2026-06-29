<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{

    use SoftDeletes, MultiTenantModelTrait, HasFactory;

    public $table = 'alerts';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'alert_text',
        'alert_link',
        'type_alert_id',
        'team_id',
        'booking_id',
        'booking_item_id',
        'customer_id',
        'customer_manifest_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['read', 'complete'])
            ->withTimestamps(); // optional
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function booking_item()
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customer_manifest()
    {
        return $this->belongsTo(CustomerManifest::class);
    }
}
