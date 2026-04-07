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
        'is_visible' => 'boolean',
        'is_disabled' => 'boolean',
        'is_enabled' => 'boolean',
        'is_custom' => 'boolean',
    ];


    protected $fillable = [
        'name',
        'description',
        'slug',
        'options',
        'conditional_logic',
        'type',
        'is_visible',
        'is_disabled',
        'is_enabled',
        'is_custom',
    ];

    const AGES = [
        ['id' => 1, 'name' => '5-17'],
        ['id' => 2, 'name' => '18-30'],
        ['id' => 3, 'name' => '31-50'],
        ['id' => 4, 'name' => '51-103'],
    ];

    const GENDERS = [
        ['id' => 1, 'name' => 'MALE'],
        ['id' => 2, 'name' => 'FEMALE'],
    ];

    const DOCUMENTS = [
        ['id' => 'final_confirmation', 'name' => 'Have a lekker adventure'],
        ['id' => 'indemnity_text', 'name' => 'indemnity text'],
    ];

    const ARRIVALS = [
        ['id' => '1', 'name' => 'No, nobody greeted us'],
        ['id' => '2', 'name' => 'Yes thanks'],
        ['id' => '3', 'name' => 'Absolutely, the staff were friendly and welcoming'],
    ];

    const SAFETY_BRIFINGS = [
        ['id' => '1', 'name' => 'Expected more'],
        ['id' => '2', 'name' => 'Covered all the points'],
        ['id' => '3', 'name' => 'Great: informative and enlightening'],
    ];

    const DRIVE = [
        ['id' => '1', 'name' => 'Way too bumpy for my liking'],
        ['id' => '2', 'name' => "Didn't hate it, but didn't love it either"],
        ['id' => '3', 'name' => 'Loved the adventure'],
    ];

    const AREA_KNOWLEDGE = [
        ['id' => '1', 'name' => "I wasn't given any info on the area"],
        ['id' => '2', 'name' => "Good"],
        ['id' => '3', 'name' => 'Informative and interesting'],
    ];

    const COMFORT_SAFETY = [
        ['id' => '1', 'name' => "No, I was unsure about the guides' safety"],
        ['id' => '2', 'name' => "Yes, I felt safe"],
        ['id' => '3', 'name' => 'The guides were excellent with safety'],
    ];




    public function question_sites()
    {
        return $this->hasMany(QuestionSite::class);
    }
}
