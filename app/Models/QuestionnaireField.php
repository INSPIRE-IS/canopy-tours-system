<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireField extends Model
{
    use HasFactory;

    protected $table = 'questionnaire_fields';

    protected $casts = [
        'options' => 'array',
    ];

    protected $fillable = [
        'question',
        'description',
        'slug',
        'options',
        'type_name',
        'conditional_logic',
        'type_input_id',
        'type_data_id',
        'created_by_id',
        'updated_by_id',
    ];

    public function questions()
    {
        return $this->belongsToMany(Questionnaire::class)
            ->withPivot(['required', 'visable', 'sort_order'])
            ->withTimestamps();
    }

    public function typeInput()
    {
        return $this->belongsTo(TypeInput::class);
    }

    public function typeData()
    {
        return $this->belongsTo(TypeData::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
