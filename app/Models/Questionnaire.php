<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasFactory;

    protected $table = 'questionnaires';

    protected $fillable = [
        'name',
        'description',
        'team_id',
        'created_by_id',
        'updated_by_id',
    ];

    public function fields()
    {
        return $this->hasMany(QuestionnaireField::class);
    }

    public function answers()
    {
        return $this->hasMany(QuestionnaireAnswer::class);
    }
}
