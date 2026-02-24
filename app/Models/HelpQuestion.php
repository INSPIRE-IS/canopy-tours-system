<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question',
        'type_help_question_id',
        'team_id',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function typeHelpQuestion()
    {
        return $this->belongsTo(TypeHelpQuestion::class);
    }

    public function answer()
    {
        return $this->hasOne(HelpAnswer::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
