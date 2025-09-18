<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireAnswer extends Model
{
    use HasFactory;
    protected $table = 'questionnaire_answers';

    protected $fillable = [
        'value',
        'questionnaire_id',
        'questionnaire_field_id',
        'customer_id',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function questionnaireField()
    {
        return $this->belongsTo(QuestionnaireField::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
