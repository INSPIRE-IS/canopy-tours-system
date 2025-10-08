<?php

namespace App\Models;

use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Questionnaire extends Model
{
    use HasFactory, MultiTenantModelTrait;

    protected $table = 'questionnaires';

    protected $hidden = [
        'pivot',
        'pivot_questionnaire_id',
        'pivot_questionnaire_field_id',
        'pivot_visable',
        'pivot_sort_order',
        'pivot_created_at',
        'pivot_updated_at',
    ];

    protected $fillable = [
        'name',
        'description',
        'season_id',
        'service_id',
        'team_id',
        'created_by_id',
        'updated_by_id',
    ];

    public function fields()
    {
        return $this->belongsToMany(QuestionnaireField::class)
            ->withPivot(['required', 'visable', 'sort_order'])
            ->withTimestamps();
    }

    public function answers()
    {
        return $this->hasMany(QuestionnaireAnswer::class);
    }
}
