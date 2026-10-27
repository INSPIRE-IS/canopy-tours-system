<?php

use App\Models\BoardingPassSection;
use App\Models\BoardingPassTemplate;
use Database\Seeders\KarkloofBoardingPassSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::table('teams')->insert([
        'id' => 3,
        'name' => 'Karkloof Canopy Tour',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('seeds the default Karkloof boarding pass and its ordered sections', function () {
    $this->seed(KarkloofBoardingPassSeeder::class);

    $template = BoardingPassTemplate::where('team_id', 3)->firstOrFail();

    expect($template->name)->toBe('Karkloof Canopy')
        ->and($template->service_id)->toBeNull()
        ->and($template->season_id)->toBeNull()
        ->and($template->is_default)->toBeTrue()
        ->and($template->is_active)->toBeTrue()
        ->and($template->footer)->toContain('disclaimer and waiver')
        ->and($template->sections)->toHaveCount(8)
        ->and($template->sections->pluck('section_tag')->all())->toBe([
            'tour_includes',
            'arrival',
            'weather',
            'restrictions',
            'tandems_children',
            'further_info',
            'cancellation_refunds',
            'what_to_bring',
        ]);
});

it('updates existing Karkloof content without duplicating it', function () {
    $this->seed(KarkloofBoardingPassSeeder::class);

    $template = BoardingPassTemplate::where('team_id', 3)->firstOrFail();
    $template->update([
        'location' => 'Old location',
        'footer' => '',
    ]);
    $template->sections()->where('section_tag', 'arrival')->update(['items' => ['Old arrival text']]);

    $this->seed(KarkloofBoardingPassSeeder::class);

    expect(BoardingPassTemplate::where('team_id', 3)->count())->toBe(1)
        ->and(BoardingPassSection::where('boarding_pass_template_id', $template->id)->count())->toBe(8)
        ->and($template->fresh()->location)->toBe('Karkloof Canopy Tour')
        ->and($template->fresh()->footer)->toContain('disclaimer and waiver')
        ->and($template->fresh()->sections->firstWhere('section_tag', 'arrival')->items)->toContain(
            'Please arrive at 20 min BEFORE your tour time.'
        );
});

it('consolidates duplicate legacy templates and sections', function () {
    $template = BoardingPassTemplate::create([
        'team_id' => 3,
        'name' => 'Karkloof Canopy',
        'location' => 'Old location',
        'address' => 'Old address',
        'footer' => '',
        'service_id' => null,
        'season_id' => null,
        'is_default' => true,
        'is_active' => true,
    ]);
    $duplicateTemplate = BoardingPassTemplate::create([
        'team_id' => 3,
        'name' => 'Karkloof Canopy',
        'location' => 'Duplicate location',
        'address' => 'Duplicate address',
        'footer' => '',
        'service_id' => null,
        'season_id' => null,
        'is_default' => true,
        'is_active' => true,
    ]);

    BoardingPassSection::create([
        'boarding_pass_template_id' => $template->id,
        'section_tag' => '',
        'title' => 'Arrival',
        'icon_path' => null,
        'items' => ['Legacy arrival text'],
        'sort_order' => 0,
        'is_active' => true,
    ]);
    BoardingPassSection::create([
        'boarding_pass_template_id' => $template->id,
        'section_tag' => 'arrival',
        'title' => 'Arrival',
        'icon_path' => null,
        'items' => ['Duplicated arrival text'],
        'sort_order' => 1,
        'is_active' => true,
    ]);
    $duplicateTemplateSection = BoardingPassSection::create([
        'boarding_pass_template_id' => $duplicateTemplate->id,
        'section_tag' => 'tour_includes',
        'title' => 'Tour Includes',
        'icon_path' => null,
        'items' => ['Duplicate template text'],
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $this->seed(KarkloofBoardingPassSeeder::class);

    expect(BoardingPassTemplate::where('team_id', 3)->count())->toBe(1)
        ->and(BoardingPassTemplate::withTrashed()->findOrFail($duplicateTemplate->id)->trashed())->toBeTrue()
        ->and(BoardingPassSection::where('boarding_pass_template_id', $template->id)->count())->toBe(8)
        ->and(BoardingPassSection::where('boarding_pass_template_id', $template->id)->where('section_tag', 'arrival')->count())->toBe(1)
        ->and(BoardingPassSection::withTrashed()->findOrFail($duplicateTemplateSection->id)->trashed())->toBeTrue();
});
