<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerIndemnityDocument extends Model
{
    use HasFactory, SoftDeletes, MultiTenantModelTrait, Auditable;

    protected $fillable = [
        'uuid',
        'document_type',
        'document_version',
        'indemnity_text',
        'indemnity_text_hash',
        'document_snapshot',
        'document_snapshot_hash',
        'answers_snapshot',
        'signer_name',
        'signer_email',
        'signer_phone',
        'guardian_name',
        'signing_place',
        'viewed_at',
        'viewed_ip',
        'viewed_user_agent',
        'signed_at',
        'signed_ip',
        'signed_user_agent',
        'signature_path',
        'signed_pdf_path',
        'signed_pdf_hash',
        'email_requested',
        'emailed_to',
        'emailed_at',
        'team_id',
        'booking_id',
        'booking_item_id',
        'customer_id',
        'customer_manifest_id',
    ];

    protected $casts = [
        'document_snapshot' => 'array',
        'answers_snapshot' => 'array',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'emailed_at' => 'datetime',
        'email_requested' => 'boolean',
    ];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
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
