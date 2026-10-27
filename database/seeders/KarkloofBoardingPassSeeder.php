<?php

namespace Database\Seeders;

use App\Models\BoardingPassSection;
use App\Models\BoardingPassTemplate;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KarkloofBoardingPassSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $team = Team::withTrashed()->find(3);

            if (!$team || $team->trashed() || !str_contains(strtolower($team->name), 'karkloof')) {
                throw new RuntimeException('Active Karkloof team with ID 3 was not found; no boarding-pass data was changed.');
            }

            $matchingTemplates = BoardingPassTemplate::withTrashed()
                ->where('team_id', $team->id)
                ->where('name', 'Karkloof Canopy')
                ->whereNull('service_id')
                ->whereNull('season_id')
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get();

            $template = $matchingTemplates->first() ?? new BoardingPassTemplate([
                'team_id' => $team->id,
                'name' => 'Karkloof Canopy',
                'service_id' => null,
                'season_id' => null,
            ]);

            $template->fill([
                'location' => 'Karkloof Canopy Tour',
                'address' => 'Portion 29 Farm Welgevonden 969, Karkloof Road, Howick',
                'footer' => 'All visitors and participants and participating in any activities and/or transport offered or arranged by Canopy Tours or third parties engaged by Canopy Tours by their signature of this document acknowledge that they have read & understand this indemnity, disclaimer and waiver (‘the Indemnity’) and agree to be bound by it, failing which he/she can choose not to board the transport and/or not to participate in the activities.',
                'is_default' => true,
                'is_active' => true,
            ]);
            $template->deleted_at = null;
            $template->save();

            foreach ($this->sections() as $sortOrder => $data) {
                $matchingSections = BoardingPassSection::withTrashed()
                    ->where('boarding_pass_template_id', $template->id)
                    ->where(function ($query) use ($data) {
                        $query->where('section_tag', $data['section_tag'])
                            ->orWhere('title', $data['title']);
                    })
                    ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('id')
                    ->get();

                $section = $matchingSections->first();

                $section ??= new BoardingPassSection([
                    'boarding_pass_template_id' => $template->id,
                ]);

                $section->fill(array_merge($data, [
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]));
                $section->deleted_at = null;
                $section->save();

                $matchingSections
                    ->where('id', '!=', $section->id)
                    ->each(function (BoardingPassSection $duplicate) {
                        if (!$duplicate->trashed()) {
                            $duplicate->delete();
                        }
                    });
            }

            $matchingTemplates
                ->where('id', '!=', $template->id)
                ->each(function (BoardingPassTemplate $duplicate) {
                    BoardingPassSection::where('boarding_pass_template_id', $duplicate->id)->delete();

                    if (!$duplicate->trashed()) {
                        $duplicate->delete();
                    }
                });
        });
    }

    private function sections(): array
    {
        return [
            [
                'section_tag' => 'tour_includes',
                'title' => 'Tour Includes',
                'icon_path' => '/images/icons/tour.png',
                'items' => [
                    'All equipment and training.',
                    'A short 4x4 trip to the drop off point.',
                    'A 2 1/2 hour fully guided zipline adventure along 12 elevated platforms, along 10 ziplines.',
                    'Small exclusive groups of 8–10 people.',
                    '2x fully trained Karkloof Canopy Tour facilitators will join you on each tour.',
                ],
            ],
            [
                'section_tag' => 'arrival',
                'title' => 'Arrival',
                'icon_path' => '/images/icons/location.png',
                'items' => [
                    'Please arrive at 20 min BEFORE your tour time.',
                    'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                    'Once the safety briefing begins late guests cannot join and the tour will be considered missed.',
                ],
            ],
            [
                'section_tag' => 'weather',
                'title' => 'Weather',
                'icon_path' => '/images/icons/cloud.png',
                'items' => [
                    'Tours are still run in the rain and will only be stopped/cancelled if lightning in the near vicinity.',
                    'Notify the team 5 days prior to your tour if you want to move or cancel your tour.',
                ],
            ],
            [
                'section_tag' => 'restrictions',
                'title' => 'Restrictions',
                'icon_path' => '/images/icons/restrictions.png',
                'items' => [
                    'This is an adventure activity requiring moderate fitness.',
                    'Recommended weight limit: 120 kg per person.',
                    'Limit is based on harness size and strap circumference (size 44 pants is a good indication)',
                    'If over 120 kg but fit your body shape may still allow safe harness fit.',
                    'Minimum age: 6 years old.',
                ],
            ],
            [
                'section_tag' => 'tandems_children',
                'title' => 'Tandems / Children',
                'icon_path' => '/images/icons/family.png',
                'items' => [
                    'We can offer guests to go tandem with one of our qualified guides.',
                    'Minimum age: 6 years old.',
                ],
            ],
            [
                'section_tag' => 'further_info',
                'title' => 'Further Info',
                'icon_path' => '/images/icons/info.png',
                'items' => [
                    'Tips aren’t required but appreciated.',
                    'Small coffee shop onsite.',
                    'Wi-Fi available at the base.',
                ],
            ],
            [
                'section_tag' => 'cancellation_refunds',
                'title' => 'Cancellation / Refunds',
                'icon_path' => '/images/icons/refund.png',
                'items' => [
                    '100% refund for cancellations 5+ days before the tour.',
                    'No refund for late arrivals or missed tours or if a guest starts a tour and does not finish.',
                    'If the company cancels you receive a full refund.',
                    'Third-party refunds handled through the agent.',
                ],
            ],
            [
                'section_tag' => 'what_to_bring',
                'title' => 'What to Bring',
                'icon_path' => '/images/icons/tshirt.png',
                'items' => [
                    'Rain jacket or rain poncho on rainy days.',
                    'A small pouch or small sling bag for your phone, should you want to take photos.',
                ],
            ],
        ];
    }
}
