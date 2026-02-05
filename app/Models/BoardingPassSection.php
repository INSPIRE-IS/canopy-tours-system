<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BoardingPassSection extends Model
{
    use HasFactory, SoftDeletes;

    public const SECTION_TITLE_ICONS = [
        ['id' => 1, 'tag' => 'arrival', 'name' => 'Arrival', 'path' => '/images/icons/location.png'],
        ['id' => 2, 'tag' => 'cancellation_refunds', 'name' => 'Cancellation / Refunds', 'path' => '/images/icons/refund.png'],
        ['id' => 3, 'tag' => 'cbd_transfers', 'name' => 'CBD Transfers', 'path' => '/images/icons/car.png'],
        ['id' => 4, 'tag' => 'further_info', 'name' => 'Further Info', 'path' => '/images/icons/info.png'],
        ['id' => 5, 'tag' => 'restrictions', 'name' => 'Restrictions', 'path' => '/images/icons/restrictions.png'],
        ['id' => 6, 'tag' => 'tandems_children', 'name' => 'Tandems / Children', 'path' => '/images/icons/family.png'],
        ['id' => 7, 'tag' => 'tour_includes', 'name' => 'Tour Includes', 'path' => '/images/icons/tour.png'],
        ['id' => 8, 'tag' => 'weather', 'name' => 'Weather', 'path' => '/images/icons/cloud.png'],
        ['id' => 9, 'tag' => 'what_to_bring', 'name' => 'What to Bring', 'path' => '/images/icons/tshirt.png'],
        ['id' => 10, 'tag' => 'what_to_bring_note', 'name' => 'What to Bring/Note', 'path' => '/images/icons/tshirt.png'],
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
