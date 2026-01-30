<?php

namespace App\Console\Commands;

use App\Models\BoardingPassSection;
use App\Models\BoardingPassTemplate;
use Illuminate\Console\Command;


class SeedCoreData extends Command
{
    protected $signature = 'seed:core-bording-pass'; // php artisan seed:core-bording-pass
    protected $description = 'Create or update core seed data for boarding pass templates.';

    public function handle()
    {
        $this->info('Seeding core data...');

        $boardingPassTemplates = [
            [
                'team_id' => 5,
                'name' => 'Magoebaskloof Boarding Pass',
                'location' => 'Magoebaskloof Adventures',
                'address' => 'GV11B, R528, Georges Valley, Limpopo',
                'footer' => '',
                'sections' => [
                    [
                        'title' => 'Tour Includes',
                        'icon' => '/images/icons/tour.png',
                        'items' => [
                            'All equipment and training.',
                            'A light meal.',
                            'A 2.5-3 hour fully guided black water tubing adventure through the Letaba Gorge.',
                            'Group size: 2-18.',
                            '2x fully trained facilitators will guide you.',
                        ],
                    ],
                    [
                        'title' => 'Arrival',
                        'icon' => '/images/icons/location.png',
                        'items' => [
                            'Please arrive at least 10–15min BEFORE your tour time.',
                            'Allow sufficient travel time — the trip will start promptly and late guests cannot be accommodated.',
                            'Alcohol and adventure do not mix. In the interest of the safety of the other guests and the guides, we reserve the right to refuse your participation on the trip if you are considered intoxicated. No refund will be given.',
                            'Once the safety briefing begins late guests cannot join and the tour will be considered missed. No refund will be given.',
                        ],
                    ],
                    [
                        'title' => 'What to Bring',
                        'icon' => '/images/icons/tshirt.png',
                        'items' => [
                            'River shoes, strops or trainers that can get wet (not flip-flops),  swimming costume, shorts and t-shirt.',
                            'Towel,  sunscreen, dry clothes and a warm jacket for after.',
                        ],
                    ],
                    [
                        'title' => 'Weather',
                        'icon' => '/images/icons/cloud.png',
                        'items' => [
                            'trips are dependent on river levels.',
                            'In the event of risky river levels, you will be given the option, on the day of you trip, to reschedule your trip or a refund will be given.',
                        ],
                    ],
                    [
                        'title' => 'Restrictions',
                        'icon' => '/images/icons/restrictions.png',
                        'items' => [
                            'This is an adventure activity requiring moderate fitness. You need to be a competent swimmer. We do not recommend that the trip is done during pregnancy.',
                            'Minimum age: 10 years old.',
                        ],
                    ],
                    [
                        'title' => 'Tandems / Children',
                        'icon' => '/images/icons/family.png',
                        'items' => [
                            'Minimum age: 10 years old.',
                        ],
                    ],
                    [
                        'title' => 'Further Info',
                        'icon' => '/images/icons/info.png',
                        'items' => [
                            "Tips aren’t required but appreciated.",
                            'Extra snacks and drinks available before and after your tour.',
                            'Wi-Fi available at the base.',
                        ],
                    ],
                    [
                        'title' => 'Cancellation / Refunds',
                        'icon' => '/images/icons/refund.png',
                        'items' => [
                            '100% refund for cancellations 7 days before the trip.',
                            'No refund for late arrivals or missed tours.',
                        ],
                    ],
                ],
            ],
            [
                'team_id' => 5,
                'service_id' => 14, // Tubing
                'season_id' => 14,
                'name' => 'Magoebaskloof Boarding Pass',
                'location' => 'Magoebaskloof Adventures',
                'address' => 'GV11B, R528, Georges Valley, Limpopo',
                'footer' => '',
                'sections' => [
                    [
                        'title' => 'Tour Includes',
                        'icon' => '/images/icons/tour.png',
                        'items' => [
                            'All equipment and training.',
                            'A light meal.',
                            'A 2.5-3 hour fully guided black water tubing adventure through the Letaba Gorge.',
                            'Group size: 2-18.',
                            '2x fully trained facilitators will guide you.',
                        ],
                    ],
                    [
                        'title' => 'Arrival',
                        'icon' => '/images/icons/location.png',
                        'items' => [
                            'Please arrive at least 10–15min BEFORE your tour time.',
                            'Allow sufficient travel time — the trip will start promptly and late guests cannot be accommodated.',
                            'Alcohol and adventure do not mix. In the interest of the safety of the other guests and the guides, we reserve the right to refuse your participation on the trip if you are considered intoxicated. No refund will be given.',
                            'Once the safety briefing begins late guests cannot join and the tour will be considered missed. No refund will be given.',
                        ],
                    ],
                    [
                        'title' => 'What to Bring',
                        'icon' => '/images/icons/tshirt.png',
                        'items' => [
                            'River shoes, strops or trainers that can get wet (not flip-flops),  swimming costume, shorts and t-shirt.',
                            'Towel,  sunscreen, dry clothes and a warm jacket for after.',
                        ],
                    ],
                    [
                        'title' => 'Weather',
                        'icon' => '/images/icons/cloud.png',
                        'items' => [
                            'trips are dependent on river levels.',
                            'In the event of risky river levels, you will be given the option, on the day of you trip, to reschedule your trip or a refund will be given.',
                        ],
                    ],
                    [
                        'title' => 'Restrictions',
                        'icon' => '/images/icons/restrictions.png',
                        'items' => [
                            'This is an adventure activity requiring moderate fitness. You need to be a competent swimmer. We do not recommend that the trip is done during pregnancy.',
                            'Minimum age: 10 years old.',
                        ],
                    ],
                    [
                        'title' => 'Tandems / Children',
                        'icon' => '/images/icons/family.png',
                        'items' => [
                            'Minimum age: 10 years old.',
                        ],
                    ],
                    [
                        'title' => 'Further Info',
                        'icon' => '/images/icons/info.png',
                        'items' => [
                            "Tips aren’t required but appreciated.",
                            'Extra snacks and drinks available before and after your tour.',
                            'Wi-Fi available at the base.',
                        ],
                    ],
                    [
                        'title' => 'Cancellation / Refunds',
                        'icon' => '/images/icons/refund.png',
                        'items' => [
                            '100% refund for cancellations 7 days before the trip.',
                            'No refund for late arrivals or missed tours.',
                        ],
                    ],
                ],
            ],
            // [
            //     'team_id' => 5, // Abseiling
            //     'service_id' => 2,
            //     'season_id' => 2,
            //     'name' => 'Magoebaskloof Boarding Pass',
            //     'location' => 'Magoebaskloof Adventures',
            //     'address' => 'GV11B, R528, Georges Valley, Limpopo',
            //     'footer' => '',
            //     'sections' => [
            //         [
            //             'title' => 'Tour Includes',
            //             'icon' => '/images/icons/tour.png',
            //             'items' => [
            //                 'All equipment and training.',
            //                 'A light meal.',
            //                 'A 2-2.5hour fully guided abseil adventure in the Letaba Gorge.',
            //                 'Group size: 2-10.',
            //                 '2x fully trained facilitators will guide you.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Arrival',
            //             'icon' => '/images/icons/location.png',
            //             'items' => [
            //                 'Please arrive at least 10–15min BEFORE your tour time.',
            //                 'Allow sufficient travel time — the trip will start promptly and late guests cannot be accommodated.',
            //                 'Alcohol and adventure do not mix. In the interest of the safety of the other guests and the guides, we reserve the right to refuse your participation on the trip if you are considered intoxicated. No refund will be given.',
            //                 'Once the safety briefing begins late guests cannot join and the tour will be considered missed. No refund will be given.',
            //             ],
            //         ],
            //         [
            //             'title' => 'What to Bring',
            //             'icon' => '/images/icons/tshirt.png',
            //             'items' => [
            //                 'Trainers, comfortable pants/ shorts, a warm jacket — mountain weather is unpredictable.',
            //                 'Costume and towel (if you would like to swim),  sunscreen.',
            //                 'You may bring your phone or camera (at own risk).',
            //             ],
            //         ],
            //         [
            //             'title' => 'Weather',
            //             'icon' => '/images/icons/cloud.png',
            //             'items' => [
            //                 'trips are dependent on river levels.',
            //                 'In the event of risky river levels, you will be given the option, on the day of you trip, to reschedule your trip or a refund will be given.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Restrictions',
            //             'icon' => '/images/icons/restrictions.png',
            //             'items' => [
            //                 'This is an adventure activity requiring moderate fitness. We do not recommend that the trip is done during pregnancy.',
            //                 'Maximum weight: 120 kg.',
            //                 'Limit is based on harness size and strap circumference.',
            //                 'If over 120 kg but fit, your body shape may still allow safe harness fit.',
            //                 'Minimum age: 13 years old.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Tandems / Children',
            //             'icon' => '/images/icons/family.png',
            //             'items' => [
            //                 'Minimum age: 13 years old.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Further Info',
            //             'icon' => '/images/icons/info.png',
            //             'items' => [
            //                 "Tips aren’t required but appreciated.",
            //                 'Extra snacks and drinks available before and after your tour.',
            //                 'Wi-Fi available at the base.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Cancellation / Refunds',
            //             'icon' => '/images/icons/refund.png',
            //             'items' => [
            //                 '100% refund for cancellations 7 days before the trip.',
            //                 'No refund for late arrivals or missed tours.',
            //             ],
            //         ],
            //     ],
            // ],
            // [
            //     'team_id' => 5, // Tree Hop
            //     'service_id' => 3,
            //     'season_id' => 3,
            //     'name' => 'Magoebaskloof Boarding Pass',
            //     'location' => 'Magoebaskloof Adventures',
            //     'address' => 'GV11B, R528, Georges Valley, Limpopo',
            //     'footer' => '',
            //     'sections' => [
            //         [
            //             'title' => 'Tour Includes',
            //             'icon' => '/images/icons/tour.png',
            //             'items' => [
            //                 'All equipment and training.',
            //                 'An hour of amazing challenges in our ancient Eucalytus trees - think flying foxes,cargo nets, suspended rafts, twisted ladders mini ziplines and more.',
            //                 'Group size: 2-10.',
            //                 '1 x fully trained facilitators will guide you.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Arrival',
            //             'icon' => '/images/icons/location.png',
            //             'items' => [
            //                 'Please arrive at least 10–15min BEFORE your tour time.',
            //                 'Allow sufficient travel time — the trip will start promptly and late guests cannot be accommodated.',
            //                 'Alcohol and adventure do not mix. In the interest of the safety of the other guests and the guides, we reserve the right to refuse your participation on the trip if you are considered intoxicated. No refund will be given.',
            //                 'Once the safety briefing begins late guests cannot join and the tour will be considered missed. No refund will be given.',
            //             ],
            //         ],
            //         [
            //             'title' => 'What to Bring',
            //             'icon' => '/images/icons/tshirt.png',
            //             'items' => [
            //                 'Trainers, comfortable pants/ shorts, a warm jacket — mountain weather is unpredictable.',
            //                 'Sunblock; rain jacket on rainy days.',
            //                 'You may bring your phone or camera (at own risk).',
            //             ],
            //         ],
            //         [
            //             'title' => 'Weather',
            //             'icon' => '/images/icons/cloud.png',
            //             'items' => [
            //                 'trips are mildly weather-dependent but operate in most conditions.',
            //                 'In the event of inclement weather, you will be given the option, on the day of you trip, to reschedule your trip or a refund will be given.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Restrictions',
            //             'icon' => '/images/icons/restrictions.png',
            //             'items' => [
            //                 'This is an adventure activity requiring moderate fitness. We do not recommend that the trip is done during pregnancy.',
            //                 'Maximum weight: 120 kg.',
            //                 'Limit is based on harness size and strap circumference.',
            //                 'If over 120 kg but fit, your body shape may still allow safe harness fit.',
            //                 'Minimum age: 10 years old.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Tandems / Children',
            //             'icon' => '/images/icons/family.png',
            //             'items' => [
            //                 'Minimum age: 10 years old.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Further Info',
            //             'icon' => '/images/icons/info.png',
            //             'items' => [
            //                 "Tips aren’t required but appreciated.",
            //                 'Extra snacks and drinks available before and after your tour.',
            //                 'Wi-Fi available at the base.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Cancellation / Refunds',
            //             'icon' => '/images/icons/refund.png',
            //             'items' => [
            //                 '100% refund for cancellations 7 days before the trip.',
            //                 'No refund for late arrivals or missed tours.',
            //             ],
            //         ],
            //     ],
            // ],
            // [
            //     'team_id' => 5, // Hiking
            //     'service_id' => 4,
            //     'season_id' => 4,
            //     'name' => 'Magoebaskloof Boarding Pass',
            //     'location' => 'Magoebaskloof Adventures',
            //     'address' => 'GV11B, R528, Georges Valley, Limpopo',
            //     'footer' => '',
            //     'sections' => [
            //         [
            //             'title' => 'Tour Includes',
            //             'icon' => '/images/icons/tour.png',
            //             'items' => [
            //                 'A light snack and bottle of water.',
            //                 'A 2hr guided hiking trail along the Letaba River, between 2km and 8km depending on the fitness of the group.',
            //                 '1 x fully trained facilitators will guide you.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Arrival',
            //             'icon' => '/images/icons/location.png',
            //             'items' => [
            //                 'Please arrive at least 10–15min BEFORE your tour time.',
            //                 'Allow sufficient travel time — the trip will start promptly and late guests cannot be accommodated.',
            //                 'Alcohol and adventure do not mix. In the interest of the safety of the other guests and the guides, we reserve the right to refuse your participation on the trip if you are considered intoxicated. No refund will be given.',
            //                 'Once the safety briefing begins late guests cannot join and the tour will be considered missed. No refund will be given.',
            //             ],
            //         ],
            //         [
            //             'title' => 'What to Bring',
            //             'icon' => '/images/icons/tshirt.png',
            //             'items' => [
            //                 'Trainers, comfortable pants/ shorts, a warm jacket — mountain weather is unpredictable.',
            //                 'Sunblock; rain jacket on rainy days.',
            //                 'You may bring your phone or camera (at own risk).',
            //             ],
            //         ],
            //         [
            //             'title' => 'Weather',
            //             'icon' => '/images/icons/cloud.png',
            //             'items' => [
            //                 'trips are mildly weather-dependent but operate in most conditions.',
            //                 'In the event of inclement weather, you will be given the option, on the day of you trip, to reschedule your trip or a refund will be given.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Restrictions',
            //             'icon' => '/images/icons/restrictions.png',
            //             'items' => [
            //                 'This is an adventure activity requiring moderate fitness.',
            //                 'Minimum age: 7 years old.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Tandems / Children',
            //             'icon' => '/images/icons/family.png',
            //             'items' => [
            //                 'Minimum age: 10 years old.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Further Info',
            //             'icon' => '/images/icons/info.png',
            //             'items' => [
            //                 "Tips aren’t required but appreciated.",
            //                 'Extra snacks and drinks available before and after your tour.',
            //                 'Wi-Fi available at the base.',
            //             ],
            //         ],
            //         [
            //             'title' => 'Cancellation / Refunds',
            //             'icon' => '/images/icons/refund.png',
            //             'items' => [
            //                 '100% refund for cancellations 7 days before the trip.',
            //                 'No refund for late arrivals or missed tours.',
            //             ],
            //         ],
            //     ],
            // ],
            [
                'team_id' => 6,
                'name' => 'Tsitsikamma Boarding Pass',
                'location' => 'Tsitsikamma Canopy Tour',
                'address' => 'Tsitsikamma Indigenous Forest, Storms River',
                'footer' => 'Please note: A Canopy Tour is an adventure activity and does come with risks. Although every effort is taken by the Canopy Tours and our staff to ensure your safety, we request that you please follow all instructions given to you by our staff either in the briefing or on the system itself. Any non compliance to safety and or rules and policies may result in a person being removed from the tour at anytime, at which time no refund will be given.',
                'sections' => [
                    [
                        'title' => 'Tour Includes',
                        'icon' => '/images/icons/tour.png',
                        'items' => [
                            'All equipment and training.',
                            'A short 4x4 trip to the drop off point.',
                            'A 2.5 - 3 hour fully guided forest canopy zipline adventure along 10 elevated platforms.',
                            'Small exclusive groups of 8-10 people.',
                            '2x fully trained Canopy Tour facilitators will join you on each tour.',
                        ],
                    ],
                    [
                        'title' => 'Arrival',
                        'icon' => '/images/icons/location.png',
                        'items' => [
                            'Please arrive at 20 min BEFORE your tour time.',
                            'Allow sufficient travel time - the tour will start promptly and late guests cannot be accommodated.',
                            'Once the safety briefing begins late guests cannot join and the tour will be considered missed.',
                        ],
                    ],
                    [
                        'title' => 'What to Bring',
                        'icon' => '/images/icons/tshirt.png',
                        'items' => [
                            'Rain jacket or rain poncho on rainy days.',
                            'A small pouch or small sling bag for your phone, should you want to take photos.',
                        ],
                    ],
                    [
                        'title' => 'Weather',
                        'icon' => '/images/icons/cloud.png',
                        'items' => [
                            'Tours are still run in the rain and will only be stopped/cancelled if lightning in the near vicinity.',
                            'Notify the team 24 hours prior to your tour if you want to move or cancel your tour.',
                        ],
                    ],
                    [
                        'title' => 'Restrictions',
                        'icon' => '/images/icons/restrictions.png',
                        'items' => [
                            'This is an adventure activity requiring moderate fitness.',
                            'Recommended weight limit: 120 kg per person.',
                            'Limit is based on harness size and strap circumference. (Mens size 44 jean should fit)',
                        ],
                    ],
                    [
                        'title' => 'Tandems / Children',
                        'icon' => '/images/icons/family.png',
                        'items' => [
                            'We can offer 2 tandems per tour.',
                            'Tandems for guests under 50 kg only.',
                            'Email: adventure@gardenroute.co.za for children under 9 years.',
                            'Minimum age: 5 years old.',
                        ],
                    ],
                    [
                        'title' => 'Further Info',
                        'icon' => '/images/icons/info.png',
                        'items' => [
                            "Tips aren't required but appreciated.",
                            'Extra snacks and drinks available before and after your tour.',
                            'Wi-Fi available at the base.',
                            'Bring a card - no cash accepted.',
                        ],
                    ],
                    [
                        'title' => 'Cancellation / Refunds',
                        'icon' => '/images/icons/refund.png',
                        'items' => [
                            '100% refund for cancellations 24+ hours before the tour.',
                            'No refund for late arrivals or missed tours.',
                            'If the company cancels, you receive a full refund.',
                            'Third-party refunds handled through the agent.',
                        ],
                    ],
                ],
            ],
            [
                'team_id' => 2,
                'name' => 'Drakensberg Boarding Pass',
                'location' => 'Drakensberg Canopy Tour',
                'address' => 'Drakensberg Sun Property, R160 off the R600, Cathkin Park Central Drakensberg, 3340',
                'footer' => 'Please note: A Canopy Tour is an adventure activity and does come with risks. Although every effort is taken by the Canopy Tours and our staff to ensure your safety, we request that you please follow all instructions given to you by our staff either in the briefing or on the system itself. Any non compliance to safety and or rules and policies may result in a person being removed from the tour at anytime, at which time no refund will be given.',
                'sections' => [
                    [
                        'title' => 'Tour Includes',
                        'icon' => '/images/icons/tour.png',
                        'items' => [
                            'All equipment and training.',
                            'A 2.5km 4x4 drive, from base to the start point and from our finish point back to base.',
                            'A 2.5 - 3 hour fully guided forest canopy zipline adventure along 11 elevated ziplines.',
                            'Groups of up to 10 people at a time, however bigger groups can be accommodated by arrangement.',
                            '2x fully trained Drakensberg Canopy Tour facilitators will join you on each tour to ensure your safe and well looked after.',
                            'A bottle of water and a KitKat to keep you hydrated and your energy levels up.',
                        ],
                    ],
                    [
                        'title' => 'Arrival',
                        'icon' => '/images/icons/location.png',
                        'items' => [
                            'Please arrive at least 20 - 30min BEFORE your tour time.',
                            'Please ensure you arrive by the requested time, as late guests may be disappointed when we are unable to accommodate them.',
                            'Once the safety briefing begins late guests might not be able to join and the tour could be considered missed.',
                        ],
                    ],
                    [
                        'title' => 'What to Bring',
                        'icon' => '/images/icons/tshirt.png',
                        'items' => [
                            'Comfortable, weather appropriate clothing and closed walking shoes.',
                            'Rain jacket on rainy days.',
                            'You may bring your phone (at own risk). Please ensure you have zip up pockets or ask at reception for options.',
                        ],
                    ],
                    [
                        'title' => 'CBD Transfers',
                        'icon' => '/images/icons/car.png',
                        'items' => [
                            'If booked, be ready at your transfer departure time.',
                            'Transfers are operated by an external supplier.',
                            'Contact us if you have not been contacted by the transfer driver.',
                        ],
                    ],
                    [
                        'title' => 'Weather',
                        'icon' => '/images/icons/cloud.png',
                        'items' => [
                            'Tours are mildly weather-dependent but operate in rain or shine.',
                            'The weather is monitored closely - tours may be postponed or cancelled due to severe weather and specifically if there is lightning in the immediate area.',
                            'Weather changes quickly; contact the team if uncertain.',
                            'Notify the team 24 hours in advance if you want to move or cancel your tour.',
                        ],
                    ],
                    [
                        'title' => 'Restrictions',
                        'icon' => '/images/icons/restrictions.png',
                        'items' => [
                            'This is an adventure activity requiring moderate fitness.',
                            'Recommended weight limit: 120 - 130kg per person.',
                            'Limit is based on harness size and strap circumference. (Mens size 44 jean should fit)',
                            'If over 120-130 kg but fit your body shape may still allow safe harness fit.',
                            'Minimum age: 6 years old. Management reserves the right to decline a child which they think, for safety reasons should not do the Canopy Tour, regardless of age.',
                            'Anyone under the influence or suspected to be under the influence of alcohol are not allowed to participate in a Canopy Tour.',
                            'Pregnant or suspected pregnant women are not allowed to do a Canopy Tour.',
                        ],
                    ],
                    [
                        'title' => 'Tandems / Children',
                        'icon' => '/images/icons/family.png',
                        'items' => [
                            '',
                        ],
                    ],
                    [
                        'title' => 'Further Info',
                        'icon' => '/images/icons/info.png',
                        'items' => [
                            'Extra snacks and drinks available before and after your tour.',
                            'We accept card and cash payments on site.',
                        ],
                    ],
                    [
                        'title' => 'Cancellation / Refunds',
                        'icon' => '/images/icons/refund.png',
                        'items' => [
                            'NO REFUNDS WILL BE GIVEN FOR THE FOLLOWING REASONS:',
                            '- No-Shows',
                            '- Not completing the tour or deciding last minute not to zipline',
                            '- Any cancellations made within 24 hours of the tour',
                            '- Late arrivals',
                            '- Postponements within 24 hours of the tour',
                        ],
                    ],
                ],
            ],
            [
                'team_id' => 7,
                'name' => 'Test Team Boarding Pass',
                'location' => 'Cape Canopy Tour',
                'address' => 'Address Placeholder',
                'footer' => '',
                'sections' => [
                    [
                        'title' => 'Tour Includes',
                        'icon' => '/images/icons/tour.png',
                        'items' => [
                            'All equipment and training.',
                            'A return 4x4 experience from through the nature reserve.',
                            'A 4-5 hour fully guided mountain top zipline adventure along 13 elevated platforms.',
                            'Small exclusive groups of 8-9 people.',
                            '2x fully trained Cape Canopy Tour facilitators will join you on each tour.',
                            'A personalized video (this is available to purchase as an optional extra).',
                        ],
                    ],
                    [
                        'title' => 'Arrival',
                        'icon' => '/images/icons/location.png',
                        'items' => [
                            'Please arrive at least 10-15min BEFORE your tour time.',
                            'Allow sufficient travel time - the tour will start promptly and late guests cannot be accommodated.',
                            'If coming from Cape Town, please factor in an extra 30-45min for irregular traffic delays.',
                            'Once the safety briefing begins, late guests cannot join and the tour will be considered missed.',
                        ],
                    ],
                    [
                        'title' => 'What to Bring',
                        'icon' => '/images/icons/tshirt.png',
                        'items' => [
                            'Warm clothes - mountain weather is unpredictable.',
                            'Sunblock & Hat; rain jacket on rainy days.',
                            'A small light backpack (optional).',
                            'You may bring your phone or camera (at own risk).',
                        ],
                    ],
                    [
                        'title' => 'CBD Transfers',
                        'icon' => '/images/icons/car.png',
                        'items' => [
                            'If booked, be ready at your transfer departure time.',
                            'Transfers are operated by an external supplier.',
                            'Contact us if you have not been contacted by the transfer driver.',
                        ],
                    ],
                    [
                        'title' => 'Weather',
                        'icon' => '/images/icons/cloud.png',
                        'items' => [
                            'Tours are mildly weather-dependent but operate in most conditions.',
                            'The weather is monitored closely - tours may be cancelled, refunded, or rescheduled.',
                            'Weather changes quickly; contact the team if uncertain.',
                            'Notify the team 24 hours in advance if you want to move or cancel your tour.',
                        ],
                    ],
                    [
                        'title' => 'Restrictions',
                        'icon' => '/images/icons/restrictions.png',
                        'items' => [
                            'This is an adventure activity requiring moderate fitness.',
                            'Recommended weight limit: 100-110 kg per person.',
                            'Limit is based on harness size and strap circumference.',
                            'If over 100-110 kg but fit, your body shape may still allow safe harness fit.',
                            'Minimum age: 5 years old.',
                        ],
                    ],
                    [
                        'title' => 'Tandems / Children',
                        'icon' => '/images/icons/family.png',
                        'items' => [
                            'We can offer 2 tandems per tour.',
                            'Tandems for guests under 50 kg only.',
                            'Email: zipline@capecanopytour.co.za for children under 9 years.',
                            'Minimum age: 5 years old.',
                            'Children under 5 may need to ride tandem with a guide.',
                        ],
                    ],
                    [
                        'title' => 'Further Info',
                        'icon' => '/images/icons/info.png',
                        'items' => [
                            "Tips aren't required but appreciated.",
                            'Extra snacks and drinks available before and after your tour.',
                            'Wi-Fi available at the base.',
                            'Bring a card - no cash accepted.',
                        ],
                    ],
                    [
                        'title' => 'Cancellation / Refunds',
                        'icon' => '/images/icons/refund.png',
                        'items' => [
                            '100% refund for cancellations 24+ hours before the tour.',
                            'No refund for late arrivals or missed tours.',
                            'If the company cancels, you receive a full refund.',
                            'Third-party refunds handled through the agent.',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($boardingPassTemplates as $templateData) {
            $sections = $templateData['sections'];
            unset($templateData['sections']);

            $template = BoardingPassTemplate::updateOrCreate(
                [
                    'team_id' => $templateData['team_id'],
                    'name' => $templateData['name'],
                    'service_id' => $templateData['service_id'] ?? null,
                    'season_id' => $templateData['season_id'] ?? null,
                ],
                array_merge($templateData, [
                    'is_default' => true,
                    'is_active' => true,
                ])
            );

            foreach ($sections as $index => $sectionData) {
                BoardingPassSection::updateOrCreate(
                    [
                        'boarding_pass_template_id' => $template->id,
                        'title' => $sectionData['title'],
                    ],
                    [
                        'icon_path' => $sectionData['icon'] ?? null,
                        'items' => $sectionData['items'],
                        'sort_order' => $index,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->info('Core data seeded successfully.');


        // php artisan seed:core-bording-pass
    }
}
