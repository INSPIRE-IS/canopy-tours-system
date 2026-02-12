<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'pax_adults',
        'pax_children',
        'pax_guides',
        'price_adult',
        'price_child',
        'price_guide',
        'team_id',
        'service_id',
        'season_id',
        'booking_inventory_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function booking_inventory()
    {
        return $this->belongsTo(BookingInventory::class);
    }
}
