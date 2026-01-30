<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardingPassSection extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'boarding_pass_sections';

    protected $fillable = [
        'boarding_pass_template_id',
        'title',
        'icon_path',
        'items',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(BoardingPassTemplate::class, 'boarding_pass_template_id');
    }
}
