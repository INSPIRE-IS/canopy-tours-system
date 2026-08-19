<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'booking_id',
        'gift_voucher_id',
        'invoice_date',
        'due_date',
        'subtotal',
        'vat',
        'total',
        'invoice_template_id',
        'type_invoice_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!empty($model->invoice_number)) {
                return;
            }
            $year = now()->format('Y');

            // Find the last invoice for this year
            $lastInvoice = self::withTrashed()
                ->whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastInvoice
                ? ((int) substr($lastInvoice->invoice_number, -6)) + 1
                : 1;

            $typeSlug = TypeInvoice::whereKey($model->type_invoice_id)->value('slug');
            $prefix = match ($typeSlug) {
                TypeInvoice::SLUG_QUOTE => 'QUO',
                TypeInvoice::SLUG_PROFORMA => 'PRO',
                TypeInvoice::SLUG_RECEIPT => 'REC',
                default => 'INV',
            };

            $model->invoice_number = sprintf("%s-%s-%06d", $prefix, $year, $nextNumber);
        });
    }


    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function template()
    {
        return $this->belongsTo(InvoiceTemplate::class, 'invoice_template_id');
    }

    public function typeInvoice()
    {
        return $this->belongsTo(TypeInvoice::class);
    }
}
