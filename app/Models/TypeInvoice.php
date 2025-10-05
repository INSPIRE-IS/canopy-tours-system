<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeInvoice extends Model
{
    use HasFactory;

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
