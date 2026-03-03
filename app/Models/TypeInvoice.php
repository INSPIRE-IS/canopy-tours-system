<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeInvoice extends Model
{
    use HasFactory;

    public const SLUG_STD = 'std';
    public const SLUG_WHOLESALE = 'wholesale';
    public const SLUG_QUOTE = 'quote';
    public const SLUG_PROFORMA = 'proforma';
    public const SLUG_RECEIPT = 'receipt';

    public $table = 'type_invoices';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function invoiceTemplates()
    {
        return $this->hasMany(InvoiceTemplate::class);
    }
}
