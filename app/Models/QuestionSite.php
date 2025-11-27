<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionSite extends Model
{
    use HasFactory;

    protected $table = 'question_site';

    protected $fillable = [
        'enabled',
        'sort_order',
        'question_id',
        'service_id',
    ];


    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
