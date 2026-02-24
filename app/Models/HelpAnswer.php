<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpAnswer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'help_question_id',
        'answer',
    ];

    public function helpQuestion()
    {
        return $this->belongsTo(HelpQuestion::class);
    }
}
