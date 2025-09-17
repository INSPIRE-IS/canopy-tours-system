<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'description', 'qty', 'price', 'subtotal', 'created_at', 'updated_at', 'deleted_at', 'team_id'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
