<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'questions';

    protected $casts = [
        'options' => 'array',
        'conditional_logic' => 'array',
    ];


    protected $fillable = [
        'name',
        'description',
        'slug',
        'options',
        'conditional_logic',
        'type',
    ];

    public function question_sites()
    {
        return $this->hasMany(QuestionSite::class);
    }
}
