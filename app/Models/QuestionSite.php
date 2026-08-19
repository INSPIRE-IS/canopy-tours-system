<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionSite extends Model
{
    use HasFactory, Auditable;

    protected $table = 'question_site';

    protected $casts = [
        'custom_options' => 'array',
    ];

    protected $fillable = [
        'enabled',
        'sort_order',
        'custom_options',
        'question_id',
        'service_id',
        'season_id',
    ];


    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
