<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardingPassSection extends Model
{
    use HasFactory, SoftDeletes;

    public const SECTION_TITLE_ICONS = [
        ['id' => 1, 'name' => 'arrival', 'path' => '/images/icons/location.png'],
        ['id' => 2, 'name' => 'cancellation_refunds', 'path' => '/images/icons/refund.png'],
        ['id' => 3, 'name' => 'cbd_transfers', 'path' => '/images/icons/car.png'],
        ['id' => 4, 'name' => 'further_info', 'path' => '/images/icons/info.png'],
        ['id' => 5, 'name' => 'restrictions', 'path' => '/images/icons/restrictions.png'],
        ['id' => 6, 'name' => 'tandems_children', 'path' => '/images/icons/family.png'],
        ['id' => 7, 'name' => 'tour_includes', 'path' => '/images/icons/task-square.svg'],
        ['id' => 8, 'name' => 'weather', 'path' => '/images/icons/cloud.png'],
        ['id' => 9, 'name' => 'what_to_bring', 'path' => '/images/icons/tshirt.png'],
        ['id' => 10, 'name' => 'what_to_bring_note', 'path' => '/images/icons/tshirt.png'],
    ];

    public const SECTION_TITLES = [
        'arrival' => 'Arrival',
        'cancellation_refunds' => 'Cancellation / Refunds',
        'cbd_transfers' => 'CBD Transfers',
        'further_info' => 'Further Info',
        'restrictions' => 'Restrictions',
        'tandems_children' => 'Tandems / Children',
        'tour_includes' => 'Tour Includes',
        'weather' => 'Weather',
        'what_to_bring' => 'What to Bring',
        'what_to_bring_note' => 'What to Bring/Note',
    ];

    public const SECTION_ICONS = [
        'car' => '/images/icons/car.png',
        'cloud' => '/images/icons/cloud.png',
        'family' => '/images/icons/family.png',
        'info' => '/images/icons/info.png',
        'location' => '/images/icons/location.png',
        'refund' => '/images/icons/refund.png',
        'restrictions' => '/images/icons/restrictions.png',
        'tour' => '/images/icons/tour.png',
        'tshirt' => '/images/icons/tshirt.png',
    ];

    public const SECTION_TITLE_ICON_MAP = [
        'arrival' => 'location',
        'cancellation_refunds' => 'refund',
        'cbd_transfers' => 'car',
        'further_info' => 'info',
        'restrictions' => 'restrictions',
        'tandems_children' => 'family',
        'tour_includes' => 'tour',
        'weather' => 'cloud',
        'what_to_bring' => 'tshirt',
        'what_to_bring_note' => 'tshirt',
    ];

    public $table = 'boarding_pass_sections';

    protected $fillable = [
        'boarding_pass_template_id',
        'title',
        'icon_path',
        'items',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(BoardingPassTemplate::class, 'boarding_pass_template_id');
    }
}
