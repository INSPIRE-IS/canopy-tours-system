<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeHelpQuestion extends Model
{
    use HasFactory;

    protected $table = 'type_help_questions';

    protected $fillable = ['id', 'name'];

    public function helpQuestions()
    {
        return $this->hasMany(HelpQuestion::class);
    }
}
