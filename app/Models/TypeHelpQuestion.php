<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeHelpQuestion extends Model
{
    use HasFactory;

    protected $table = 'type_help_questions';

    protected $fillable = ['id', 'name', 'role_ids', 'order_id'];

    protected $casts = [
        'role_ids' => 'array',
        'order_id' => 'integer',
    ];

    public function helpQuestions()
    {
        return $this->hasMany(HelpQuestion::class);
    }
}
