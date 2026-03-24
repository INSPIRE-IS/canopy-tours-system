<?php

namespace App\Console\Commands;

use App\Models\HelpAnswer;
use App\Models\HelpQuestion;
use App\Models\Team;
use App\Models\TypeHelpQuestion;
use Illuminate\Console\Command;

class SeedHelpQuestions extends Command
{
    protected $signature = 'seed:help-questions {--team=} {--with-samples}';
    // php artisan seed:help-questions --team=1 --with-samples
    // php artisan seed:help-questions --with-samples
    protected $description = 'Seed help question categories and optional sample questions.';

    public function handle(): int
    {
        $this->info('Seeding help question categories...');

        $adminOnly = [1, 2];
        $categories = [
            ['name' => 'General', 'role_ids' => null],
            ['name' => 'Sites (Teams)', 'role_ids' => $adminOnly],
            ['name' => 'Users', 'role_ids' => $adminOnly],
            ['name' => 'Roles', 'role_ids' => $adminOnly],
            ['name' => 'Permissions', 'role_ids' => $adminOnly],
            ['name' => 'Wholesalers', 'role_ids' => null],
            ['name' => 'Wholesaler Contacts', 'role_ids' => null],
            ['name' => 'Wholesaler Rates', 'role_ids' => null],
            ['name' => 'Services', 'role_ids' => null],
            ['name' => 'Seasons', 'role_ids' => null],
            ['name' => 'Inventory', 'role_ids' => null],
            ['name' => 'Inventory Overrides', 'role_ids' => null],
            ['name' => 'Closeouts', 'role_ids' => null],
            ['name' => 'Marketing', 'role_ids' => null],
            ['name' => 'Discounts', 'role_ids' => null],
            ['name' => 'Promotions', 'role_ids' => null],
            ['name' => 'Vouchers', 'role_ids' => null],
            ['name' => 'Gift Vouchers', 'role_ids' => null],
            ['name' => 'Bookings - Create', 'role_ids' => null],
            ['name' => 'Bookings - Edit', 'role_ids' => null],
            ['name' => 'Bookings - Payments', 'role_ids' => null],
            ['name' => 'Bookings - Cancellations', 'role_ids' => null],
            ['name' => 'Invoices', 'role_ids' => null],
            ['name' => 'Boarding Passes', 'role_ids' => null],
            ['name' => 'Manifests', 'role_ids' => null],
            ['name' => 'Check-in', 'role_ids' => null],
            ['name' => 'Questionnaires', 'role_ids' => null],
            ['name' => 'Feedback', 'role_ids' => null],
            ['name' => 'Messaging', 'role_ids' => null],
            ['name' => 'Alerts', 'role_ids' => $adminOnly],
            ['name' => 'Audit Logs', 'role_ids' => $adminOnly],
            ['name' => 'Reports', 'role_ids' => $adminOnly],
            ['name' => 'Calendar / Availability', 'role_ids' => null],
            ['name' => 'System / Settings', 'role_ids' => $adminOnly],
        ];

        foreach ($categories as $index => $category) {
            TypeHelpQuestion::updateOrCreate([
                'name' => $category['name']
            ], [
                'name' => $category['name'],
                'order_id' => $index + 1,
                'role_ids' => $category['role_ids'],
            ]);
        }

        $this->info('Categories seeded: ' . count($categories));

        if (!$this->option('with-samples')) {
            $this->info('No sample questions seeded. Use --with-samples to add placeholders.');
            return self::SUCCESS;
        }

        $teamId = $this->option('team');
        if ($teamId !== null && !Team::whereKey($teamId)->exists()) {
            $this->error('Team not found for --team=' . $teamId);
            return self::FAILURE;
        }

        $questions = $this->sampleQuestions();
        foreach ($questions as $item) {
            $category = TypeHelpQuestion::where('name', $item['category'])->first();
            if (!$category) {
                $this->warn('Skipping question, missing category: ' . $item['category']);
                continue;
            }

            $description = $item['description'] ?? $item['answer'] ?? '';
            $where = $item['where'] ?? '';
            $businessRules = $item['business_rules'] ?? '';
            $examples = $item['examples'] ?? '';
            $formattedAnswer = $this->formatAnswer($description, $where, $businessRules, $examples);

            $question = HelpQuestion::updateOrCreate(
                [
                    'question' => $item['question'],
                    'type_help_question_id' => $category->id,
                    'team_id' => $teamId,
                ],
                [
                    'is_internal' => (bool) ($item['is_internal'] ?? false),
                ]
            );

            HelpAnswer::updateOrCreate(
                ['help_question_id' => $question->id],
                ['answer' => $formattedAnswer]
            );
        }

        $this->info('Sample help questions seeded: ' . count($questions));

        return self::SUCCESS;
    }

    protected function sampleQuestions(): array
    {
        return [
            [
                'category' => 'General',
                'question' => 'What is the purpose of the Help Questions?',
                'description' => 'Internal knowledge base for common workflows, policies, and troubleshooting.',
                'where' => '<a href="/admin/help-questions">Admin > Help & Center</a>',
                'business_rules' => 'Questions can be team-specific or generic. Internal-only items are hidden for non-admin users.',
                'is_internal' => true,
            ],
            [
                'category' => 'Sites (Teams)',
                'question' => 'What is a site/team?',
                'description' => 'Teams represent each canopy location; data is scoped per team.',
                'where' => '<a href="/admin/teams">Admin > Site</a>',
                'business_rules' => 'Bookings, services, and users are scoped to the selected team.',
                'is_internal' => true,
            ],
            [
                'category' => 'Users',
                'question' => 'How do I add a staff user?',
                'description' => 'Create staff user accounts and assign access.',
                'where' => '<a href="/admin/users">Admin > Users</a>',
                'business_rules' => 'Assign at least one role. Emails must be unique.',
                'is_internal' => true,
            ],
            [
                'category' => 'Roles',
                'question' => 'Which role should I give front-desk operators?',
                'description' => 'Roles bundle permissions for staff access.',
                'where' => '<a href="/admin/roles">Admin > Roles</a>',
                'business_rules' => 'Permissions are managed on roles, not users. Admin controles available roles and permissions.',
                'is_internal' => true,
            ],
            [
                'category' => 'Permissions',
                'question' => 'Why can I not see a menu item?',
                'description' => 'Permissions control access to menus and actions.',
                'where' => '<a href="/admin/permissions">Admin > Permissions</a>',
                'business_rules' => 'Permissions are assigned to roles and enforced by gates(these are controlled access created and by admin).',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesalers',
                'question' => 'When should I use a wholesaler booking?',
                'description' => 'Wholesalers are trade partners with contracted rates.',
                'where' => '<a href="/admin/wholesalers">Admin > Wholesalers</a>',
                'business_rules' => 'Wholesaler rates override standard rates when a booking is marked as wholesaler. Rates are matched by service + season; if no rate exists, standard pricing applies.',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesaler Contacts',
                'question' => 'Where do I store wholesaler contact details?',
                'description' => 'Store contact people for each wholesaler.',
                'where' => '<a href="/admin/wholesaler-contacts">Admin > Wholesaler Contacts</a>',
                'business_rules' => 'Contacts must be linked to a wholesaler.',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesaler Rates',
                'question' => 'Why are rates lower for this booking?',
                'description' => 'Wholesaler rates apply by service and season.',
                'where' => '<a href="/admin/wholesaler-rates">Admin > Wholesaler Rates</a>',
                'business_rules' => 'Rates apply only when a booking is marked as wholesaler.',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'Where is capacity and duration set?',
                'description' => 'Services define capacity, duration, and base pricing.',
                'where' => '<a href="/admin/services">Admin > Services</a>',
                'business_rules' => 'Capacity is enforced on bookings; duration drives the time-range displayed on calendars. Services also use visibility (Draft/Bookable/Gift/Both) to control where they appear (front booking vs gift flow).',
                'examples' => 'If a service capacity is 8, bookings cannot exceed 8 unless Override Capacity is enabled for that service and selected on the booking.',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'How do service visibility settings work?',
                'description' => 'Service visibility controls whether a service appears on booking and gift flows.',
                'where' => '<a href="/admin/services">Admin > Services</a>',
                'business_rules' => 'Visibility options: Draft (hidden), Bookable, Gift Only, or Bookable + Gift. Draft hides the service everywhere until changed. Front booking list shows Bookable/Both. Gift-only services are intended for gift voucher purchase and do not appear in admin booking calendars.',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'Gift services vs bookable services (what shows where?)',
                'description' => 'Visibility controls which actions are shown on the front site and in admin bookings.',
                'where' => '<a href="/admin/services">Admin > Services</a>, <a href="/front/welcome">Front > Welcome</a>, <a href="/front/activity">Front > Activities</a>',
                'business_rules' => 'Bookable services show “Book Now” on the front activity pages and appear in admin booking calendars. Gift-only services show “Buy as a Gift” and do not appear in admin bookings. Bookable + Gift shows both actions. Draft/Hidden shows nowhere.',
                'examples' => 'If a service is Gift Only, users can purchase a gift voucher from the front site but cannot book dates. If a service is Bookable + Gift, both booking and gift purchase are available.',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'How do I clone a service?',
                'description' => 'Cloning creates a full copy of the service and its related setup.',
                'where' => 'Open a service detail view from <a href="/admin/services">Admin > Services</a> and click Clone Service.',
                'business_rules' => 'Clone copies service fields, seasons, inventories, time slots, inventory days, and closeouts. The clone starts as Draft (hidden) and must be set to Bookable/Gift/Both to appear.',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'What happens when I delete a service?',
                'description' => 'Deleting a service soft-deletes its related setup.',
                'where' => '<a href="/admin/services">Admin > Services</a>',
                'business_rules' => 'Deleting a service will soft-delete its seasons, inventory, time slots, inventory days, closeouts, promotions, wholesaler rates, service fields, and service file. Discount links are detached but discounts are not deleted.',
                'examples' => 'If you delete a service with two seasons, those seasons and their inventory/time slots are also soft-deleted so they no longer appear in Seasons or Bookings.',
                'is_internal' => true,
            ],
            [
                'category' => 'Seasons',
                'question' => 'Why is a date not bookable?',
                'description' => 'Seasons control availability dates for services.',
                'where' => '<a href="/admin/seasons">Admin > Seasons</a>',
                'business_rules' => 'Bookings must fall within season dates. New seasons auto-seed questionnaires; booking/feedback check-in will auto-seed if missing.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How are time slots generated?',
                'description' => 'Inventory defines dates and time slots per season.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a>',
                'business_rules' => 'Weekly day selections + time slots generate availability across a season. Closeouts remove specific days/times from availability.',
                'examples' => 'If a season runs 1-31 May and you select Mondays + Wednesdays with 09:00 and 13:00, those two slots appear on every Monday and Wednesday in May. To block 13:00 on May 15, add a closeout for that date/time.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'What is the difference between inventory and closeouts?',
                'description' => 'Inventory creates availability; closeouts remove it.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a> and <a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Inventory defines the recurring weekly schedule for a season. Closeouts are exceptions that block specific dates/times without changing the base schedule.',
                'examples' => 'If Fridays at 10:00 are available all season, add a closeout for 2026-06-12 at 10:00 to block only that one slot.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'Where does the current inventory model fall short?',
                'description' => 'Weekly schedules expand across the full season, which can be too broad for date-specific needs.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a> and <a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Current inventory applies selected weekdays + time slots to every matching day in the season. It does not allow “open only for these exact dates” without using closeouts to block everything else.',
                'examples' => 'If you only want 09:00 on May 5–7, the current system forces you to open 09:00 for every matching weekday across the season, then add closeouts for the days you do NOT want. Closeouts can remove dates but cannot create availability that never existed. see <a href="/admin/inventory-slot-ranges">Admin > Date Overrides</a> for a more flexible solution.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How do Date Overrides work?',
                'description' => 'Date overrides replace the weekly schedule for specific dates without changing the base inventory.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Weekly schedule is the default. If a date override exists for a date, only those override slots are used for that date. Overrides can use custom times not present in weekly inventory. Internal-only overrides show on admin calendars only. Closeouts still apply after overrides.',
                'examples' => 'Weekly schedule: Mon/Wed/Fri 09:00 + 13:00. Date override: May 5-7 with only 10:00. Result: May 5-7 shows only 10:00; all other Mon/Wed/Fri still show 09:00 + 13:00. If you mark the override internal-only, it appears in admin calendars but not on the front booking calendar.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'When should I use Date Overrides?',
                'description' => 'Use date overrides when you need custom availability for a specific date range without changing the weekly schedule.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Overrides apply only to the selected dates and replace the weekly schedule for those dates. Override-only slots are hidden on other dates. Internal-only overrides show on admin calendars only. If a capacity override is set, that capacity is used for those override slots. Closeouts can still block slots after overrides are applied.',
                'examples' => 'If your weekly schedule is 09:00 + 13:00 but you need 12:00 + 14:00 on Mar 23-25, add a date override for those dates with 12:00 + 14:00. Those dates show only 12:00 + 14:00; the rest of the season remains 09:00 + 13:00. If you set capacity override = 6, those slots cap at 6 seats even if the service capacity is higher.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'Can I include default weekly slots in a date override?',
                'description' => 'Yes. Date Overrides can prefill the weekly inventory slots so you only remove what you don’t want.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Use the “Include default weekly slots” toggle to copy the current weekly time slots into the override list. You can then remove any slots you want to exclude for that date range. Turning the toggle off removes the default slots from the list.',
                'examples' => 'If the weekly slots are 08:00 and 09:00, enabling Include Defaults adds both. You can remove 08:00 to keep only 09:00 for those dates.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory Overrides',
                'question' => 'When do we use an inventory override?',
                'description' => 'Override base rates for a specific inventory.',
                'where' => '<a href="/admin/inventories-overrides">Admin > Inventory Overrides</a>',
                'business_rules' => 'Allowed only when season.allow_internal_price_override is true. Overrides clear promos/discounts/wholesaler pricing for that booking.',
                'examples' => 'If a private group needs a custom price on 10 June at 09:00, create an inventory override for that inventory record. The booking will use the override price and will ignore promos/discounts/wholesaler rates.',
                'is_internal' => true,
            ],
            [
                'category' => 'Closeouts',
                'question' => 'How do we block a day or slot?',
                'description' => 'Closeouts block dates or time slots from booking.',
                'where' => '<a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Closed slots are unavailable in admin and front calendars. Closeouts can target specific weekdays and time slots over a date range.',
                'examples' => 'If you select a date range 10-14 July, weekdays Mon-Fri, and time slot 09:00, then 09:00 is blocked on every weekday in that range. Leaving time slots empty blocks all slots for those dates. To close all Mondays and Tuesdays for a season, set the closeout date range to the full season, pick Mon + Tue, and leave time slots empty.',
                'is_internal' => true,
            ],
            [
                'category' => 'Closeouts',
                'question' => 'Can closeouts solve date-specific availability?',
                'description' => 'Closeouts can block availability but cannot add it.',
                'where' => '<a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Closeouts are useful for removing specific days/times from an existing weekly schedule. They do not create new availability for a date that was never opened by inventory.',
                'examples' => 'If a season has no Monday slots, a closeout cannot open Mondays. If Mondays are open all season but you only want two specific Mondays, you can keep the weekly schedule and close out every other Monday (workaround).',
                'is_internal' => true,
            ],
            [
                'category' => 'Marketing',
                'question' => 'Where do we manage guest-facing promos?',
                'description' => 'Marketing includes discounts and promotions.',
                'where' => '<a href="/admin/discounts">Admin > Marketing (Discounts)</a>',
                'business_rules' => 'Discounts and promos are managed separately.',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'What is the difference between discounts and promos?',
                'description' => 'Discount rules applied to bookings or invoices.',
                'where' => '<a href="/admin/discounts">Admin > Discounts</a>',
                'business_rules' => 'Discounts are separate from promo codes. Discounts can be per-line (per pax) or invoice-level. Per-line discounts reduce individual rates; invoice-level discounts apply to the total after all per-line logic. Discounts do not have booking/activity windows.',
                'examples' => 'A 10% line discount for Adults reduces adult rates only. A 10% invoice discount reduces the full total after line-level discounts are applied.',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'What are line vs invoice discounts?',
                'description' => 'Line discounts apply to specific pax types; invoice discounts apply to the total.',
                'where' => '<a href="/admin/discounts">Admin > Discounts</a> and Admin booking create/edit',
                'business_rules' => 'Line discounts reduce the selected pax type rates (adult/child/guide). Invoice discounts apply once to the total after line discounts. Only one invoice discount should be applied at a time.',
                'examples' => 'If a booking has 2 Adults + 1 Child and you apply a 15% Child line discount, only the child rate is reduced. If you then apply a 5% invoice discount, it applies to the new total.',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'Are value (R) discounts supported?',
                'description' => 'Discounts can be percent-based today; value-based discounts are planned.',
                'where' => '<a href="/admin/discounts">Admin > Discounts</a>',
                'business_rules' => 'Current discount logic uses percent-off only. Value (R) discounts will require a discount type field and updated pricing logic.',
                'examples' => 'If you need to discount R100 off a total, this is not supported yet; use a percent discount instead.',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'How do discount types apply to pricing?',
                'description' => 'Per-line discounts apply to a specific pax type; invoice discounts apply to the total.',
                'where' => '<a href="/admin/discounts">Admin > Discounts</a>',
                'business_rules' => 'When a promotion is applied, per-line discounts are skipped. Invoice-level discounts apply at the end and do not stack with other invoice-level discounts. Discounts can be limited to specific services.',
                'examples' => 'If a promo code is applied to a booking, line discounts (e.g., “Local Resident 10%”) will not apply. An invoice-level discount can still be applied if selected.',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'Why is a discount not showing on a booking?',
                'description' => 'Discount visibility depends on service and context.',
                'where' => 'Admin booking create/edit',
                'business_rules' => 'Discounts only appear for services they are linked to. If a promotion is selected, per-line discounts are not applied. Invoice discounts are exclusive and apply only when selected.',
                'examples' => 'If a discount is linked to “Canopy Tour A” but the booking is for “Canopy Tour B”, it will not show. If a promo code is applied, the discount list may be empty.',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Why is a promo not selectable?',
                'description' => 'Promo codes use booking and activity windows.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Booking window uses today; activity window uses the booking date (activity date). Admin sees all; front only shows active promos with internal_use_only=false. If the activity date is outside the promo activity window, the promo will be invalid.',
                'examples' => 'If a promo’s booking window is Feb 1–Feb 28 and today is March 3, it will be expired (not selectable). If today is within the booking window but the activity date is May 5 and the promo activity window is April only, it will show as invalid.',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'How do booking dates vs activity dates work for promos?',
                'description' => 'Promos have two date ranges: booking window and activity window.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Booking window controls when the promo can be used (today must be within booking range). Activity window controls which activity dates are eligible (booking date must fall within activity range). Booking dates are not tied to seasons; activity dates must be within a season for the selected service.',
                'examples' => 'Booking window: Feb 1–Feb 28. Activity window: May 1–May 31. You can book in February for a May activity date. Booking in March won’t allow the promo even if the activity date is in May.',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'What promotion types are available?',
                'description' => 'Promotions can change pricing by percent, fixed value, tiers, or time slots.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Types include percent, fixed value, graduated, varied child, volume discount, and time-slot promos. Graduated promos override total price based on pax count. Time-slot promos apply only to selected days/time slots.',
                'examples' => '
                 

<strong>* Percent Fields</strong>: <strong>Value </strong>(%). <strong>Logic</strong>: Percent off booking total. A &ldquo;10% off&rdquo; promo reduces each pax rate by 10%. Fixed Value <strong>Fields</strong>: Value (R). <strong>Logic</strong>: Fixed amount off booking total. <strong>* Graduated Fields</strong>: Lookup table (pax count &rarr; total). A &ldquo;Graduated&rdquo; promo can set the total to R1500 for 4 pax regardless of base price.&nbsp;<strong>Logic</strong>: Sets booking total to lookup value for matched pax. Generally the larger the group the more discount. <strong>* Varied Child Fields:</strong> Min adults + Child discount %, or tiers. <strong>Logic</strong>: Child-only discount when min adults/tier matched. <strong>* Volume Discount Fields:</strong> Tiers (pax range, type, value). <strong>Logic</strong>: Best tier applies; percent or fixed value off booking total. <strong>* Time Slot Fields:</strong> Season, Days, optional Time Slots, <strong>Value </strong>(%). <strong>Logic</strong>: Percent off booking total for selected days; no slots = all slots. -- <em>Day/time filters: if no days are selected, the promo applies to all days. If days are selected and no time slots are checked, it applies to every slot for those days.</em></p>',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'How do promotion types affect totals?',
                'description' => 'Each promo type calculates pricing differently.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Percent and fixed-value adjust rates; graduated overrides total by pax count; varied-child discounts child rates when adult minimum is met; volume tiers apply based on pax thresholds; time-slot promos apply only to selected days/time slots.',
                'examples' => 'Varied-child: 2 adults + 1 child with min_adults=2 gives child discount only if at least 2 adults are present. Volume: 1–3 pax = 5%, 4–6 pax = 10%.',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Why do promos and discounts not stack?',
                'description' => 'Promotions override discount logic in pricing.',
                'where' => 'Admin booking create/edit',
                'business_rules' => 'When a promotion is applied, per-line discounts are skipped. This prevents double-discounting. Invoice-level discounts can still apply if explicitly selected.',
                'examples' => 'If a promo is selected, you will not see line discounts apply. To discount further, use the invoice discount selector.',
                'is_internal' => true,
            ],
            [
                'category' => 'Vouchers',
                'question' => 'How do vouchers apply to bookings?',
                'description' => 'Voucher codes reduce booking totals.',
                'where' => '<a href="/admin/vouchers">Admin > Vouchers</a>',
                'business_rules' => 'Voucher codes apply at checkout.',
                'is_internal' => true,
            ],
            [
                'category' => 'Gift Vouchers',
                'question' => 'How are gift vouchers redeemed?',
                'description' => 'Gift vouchers are sold on the front site.',
                'where' => '<a href="/front/voucher">Front > Gift Voucher</a>',
                'business_rules' => 'Redeemed during payment using the voucher code.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'Can we book children only?',
                'description' => 'Create bookings from the admin calendar.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'At least 1 adult or 1 child is required. Selected slot is required to save. Capacity is enforced against remaining slots; Override Capacity allows exceeding capacity only when the service allows it. Override Time Slots opens a date-override modal to add internal-only slots for the selected booking date.',
                'examples' => 'If a slot has 4 remaining and you add 3 adults + 2 children, the form warns you unless Override Capacity is checked (and allowed). If you need an extra 12:00 slot on a specific day, toggle Override Time Slots and add it as an internal-only date override.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'Why did my total change after saving?',
                'description' => 'Totals are recalculated from per‑pax rates and rounded to 2 decimals on save.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Booking totals are derived from per‑pax rates (adult/child/guide). Rates and per‑pax totals are rounded to 2 decimals, then the booking total is rounded to 2 decimals. This prevents drift between the calculator, saved booking, and invoice. When a promo or discount is applied, changing pax re-runs pricing to keep totals consistent; promo input is hidden whenever a discount (line item or invoice) is active.',
                'examples' => 'If a promo gives a rate like 722.50, the saved total should remain consistent (e.g., 4 pax × 722.50 = 2,890.00). If you previously saw 2,905 → 2,904 after save, resaving should now keep the same total.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'What is Override Capacity vs Override Time Slots?',
                'description' => 'They control different things: capacity vs availability.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Override Capacity allows exceeding the service capacity for a booking (only if the service allows it). Override Time Slots lets you add a date-specific slot (internal-only by default) without changing the weekly schedule.',
                'examples' => 'Override Capacity: allow 9 pax on a slot with capacity 8 for an exception. Override Time Slots: add a 12:00 slot for one day even if the weekly schedule is only 09:00 + 13:00.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Edit',
                'question' => 'Why does editing reissue invoices?',
                'description' => 'Edit bookings and reissue documents.',
                'where' => 'Open a booking from <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Edits reissue invoice and boarding pass. Expired seasons are included for the current booking so legacy bookings can be edited. The edit calendar considers the current booking pax for capacity checks, and Override Capacity can be used only when the service allows it.',
                'examples' => 'If a booking already holds 4 seats and the slot capacity is 8, you can increase it up to 8 on edit. To exceed 8, Override Capacity must be enabled (and allowed).',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'How do we record a manual payment?',
                'description' => 'Record manual or simulated payments.',
                'where' => 'Open a booking > Payments or <a href="/admin/booking-payments/create/BOOKING_ID">/admin/booking-payments/create/BOOKING_ID</a>',
                'business_rules' => 'Totals are VAT-inclusive; simulated payments store ex-VAT and VAT amounts. Payment records update booking fee totals.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Cancellations',
                'question' => 'How do we cancel a slot?',
                'description' => 'Cancel a booking or a full slot.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a> (Tools menu)',
                'business_rules' => 'Cancel Slot removes all items for date/time/service; deletes the booking if empty.',
                'is_internal' => true,
            ],
            [
                'category' => 'Invoices',
                'question' => 'Why are totals VAT inclusive?',
                'description' => 'Invoices are generated per booking.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Totals are VAT-inclusive; VAT is shown as a breakdown line. Invoice notes use the site invoice note, not per-booking notes.',
                'is_internal' => true,
            ],
            [
                'category' => 'Boarding Passes',
                'question' => 'Why is a boarding pass missing?',
                'description' => 'Boarding passes are generated from templates.',
                'where' => 'Templates: <a href="/admin/boardingpass">Admin > Boarding Pass Templates</a>. Preview from booking tools.',
                'business_rules' => 'Template selection prefers team+service+season; fallback to team default.',
                'is_internal' => true,
            ],
            [
                'category' => 'Manifests',
                'question' => 'Where does the manifest list come from?',
                'description' => 'Manifests list guests for a booking.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Manifest rows fall back to placeholders when customer data is missing.',
                'is_internal' => true,
            ],
            [
                'category' => 'Manifests',
                'question' => 'What does Indemnity mean on the manifest?',
                'description' => 'Indemnity reflects whether the guest completed the waiver.',
                'where' => 'Booking Add > Customer Manifest',
                'business_rules' => 'The Indemnity badge maps to `customer_manifests.is_indemnity`. It is set when the guest completes the check-in/waiver (signature). It is not the same as Arrived.',
                'examples' => 'A guest can complete Indemnity online but still be marked as No Show/Cancelled in Arrived.',
                'is_internal' => true,
            ],
            [
                'category' => 'Check-in',
                'question' => 'How do we send a check-in link?',
                'description' => 'Check-in links are signed and shared with guests.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Signed links required; questionnaires auto-seed if missing. Check-in links expire at activity start time + 3 hours (booking date + time slot). If the signature expires but the activity window is still valid, the link is reissued automatically on open. The top Check-in button on Admin > Bookings is disabled if there are no bookings for the selected date or if the selected date is in the past.',
                'examples' => 'If you pick a day with no bookings, the Check-in button is disabled. If a guest opens an old link before the 3‑hour window ends, the system reissues a signed link automatically.',
                'is_internal' => true,
            ],
            [
                'category' => 'Check-in',
                'question' => 'How do we mark Arrived / No Show / Cancelled?',
                'description' => 'Arrival status is tracked per guest on the manifest.',
                'where' => 'Booking Add > Customer Manifest (Arrived toggle) and Checkin Detail modal.',
                'business_rules' => 'Arrival status is stored on `customer_manifests.attendance_status` with values `no_show`, `arrived`, `cancelled` (default `no_show`). On the Booking Add list, Arrived toggles in a cycle (No → Yes → Cancelled → No). In Checkin Detail, you can select the status with 3 buttons and also update notes/lunch option, then Save.',
                'examples' => 'If a guest checks in but does not arrive, set Arrived to No. If they arrived, set Yes. If the guest cancels, set Cancelled.',
                'is_internal' => true,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'Why do age fields not store literal ages?',
                'description' => 'Questionnaire templates and responses.',
                'where' => '<a href="/admin/questionnaire">Admin > Questionnaires</a>',
                'business_rules' => 'Age values store age group IDs, not literal ages.',
                'is_internal' => true,
            ],
            [
                'category' => 'Feedback',
                'question' => 'When are feedback forms available?',
                'description' => 'Guest feedback forms.',
                'where' => '<a href="/front/feedback-questionnaire">Front > Feedback Questionnaire</a>',
                'business_rules' => 'Links are signed and available after activity dates. The top Feedback button on Admin > Bookings is disabled if there are no bookings for the selected date, if the date is in the future, or if no check-ins have been completed for that date.',
                'examples' => 'If a booking is for next week, Feedback is disabled until the activity date passes. If there are bookings today but no guests have completed check-in, Feedback remains disabled.',
                'is_internal' => true,
            ],
            [
                'category' => 'Check-in',
                'question' => 'Why does the check-in screen say “complete” or not complete?',
                'description' => 'Completion is per booking item, not per individual.',
                'where' => '<a href="/front/booking-questionnaire/checkin">Front > Check-in</a>',
                'business_rules' => 'The completion message only appears once all pax (adults + children) for the booking item have submitted indemnity. If only some guests are done, the link stays open so the remaining guests can complete their forms.',
                'examples' => 'If a booking has 4 pax and only 1 has completed, the screen stays open and does not mark complete. Share the same link with the remaining guests.',
                'is_internal' => true,
            ],
            [
                'category' => 'Messaging',
                'question' => 'Where are manual guest messages logged?',
                'description' => 'Internal message threads between staff.',
                'where' => '<a href="/admin/messenger">Admin > Messages</a>',
                'business_rules' => 'Unread count appears in the header.',
                'is_internal' => true,
            ],
            [
                'category' => 'Alerts',
                'question' => 'What triggers alerts?',
                'description' => 'System alerts for staff users.',
                'where' => '<a href="/admin/user-alerts">Admin > User Alerts</a>',
                'business_rules' => 'Alerts track read and complete state per user.',
                'is_internal' => true,
            ],
            [
                'category' => 'Audit Logs',
                'question' => 'What actions are recorded?',
                'description' => 'Audit trail for key actions.',
                'where' => '<a href="/admin/audit-logs">Admin > Audit Logs</a>',
                'business_rules' => 'Logs include booking changes, notes, emails, and payments.',
                'is_internal' => true,
            ],
            [
                'category' => 'Reports',
                'question' => 'Why do reports not show my team data?',
                'description' => 'Summary and operational reports.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'Reports are team-scoped.',
                'is_internal' => true,
            ],
            [
                'category' => 'Calendar / Availability',
                'question' => 'Why does a slot show full on edit?',
                'description' => 'Capacity and availability across dates.',
                'where' => 'Admin calendar <a href="/admin/bookings">Admin > Bookings</a> and front calendar <a href="/front/activity/calendar">/front/activity/calendar</a>',
                'business_rules' => 'Availability is based on booked pax and capacity (including date-override capacity). The edit calendar adds the current booking pax back into remaining availability so the slot does not appear full for its own booking. Internal-only date overrides show on admin calendars only.',
                'examples' => 'If a slot capacity is 8 and the booking already has 4 seats, edit should still show 4 remaining. If the slot is full in the front calendar, check for internal-only overrides or closeouts.',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'Where are VAT rates configured?',
                'description' => 'System settings and lookups.',
                'where' => 'Admin > Settings (varies by module).',
                'business_rules' => 'VAT rates are configured in Lookups > VAT Rate. Settings toggles control booking UI options like pickup/dropoff and send-invoice/quote checkboxes.',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'What do the booking settings toggles do?',
                'description' => 'Settings control what fields and actions appear in booking flows.',
                'where' => 'Admin > Settings',
                'business_rules' => 'Enable Pickup/Dropoff shows or hides pickup/dropoff fields. Auto Send Invoice/Quote controls default send behavior on booking create/update. Show Send Invoice/Quote Checkbox controls whether staff can manually choose to send these on save. Enable Assign Staff shows the staff assignment panel on Booking Add.',
                'examples' => 'If Auto Send Invoice is OFF and Show Send Invoice Checkbox is ON, staff will see a checkbox and can choose to send. If Auto Send Invoice is ON and Show Send Invoice Checkbox is OFF, invoices always send automatically.',
                'is_internal' => true,
            ],
        ];
    }

    protected function formatAnswer(string $description, string $where = '', string $businessRules = '', string $examples = ''): string
    {
        $description = trim($description);
        $where = trim($where);
        $businessRules = trim($businessRules);
        $examples = trim($examples);

        $parts = [];
        $parts[] = '<p><strong>Description:</strong> ' . ($description !== '' ? $description : 'N/A') . '</p>';
        $parts[] = '<p><strong>Where:</strong> ' . ($where !== '' ? $where : 'N/A') . '</p>';
        $parts[] = '<p><strong>Business Rules:</strong> ' . ($businessRules !== '' ? $businessRules : 'N/A') . '</p>';
        if ($examples !== '') {
            $parts[] = '<p><strong>Example:</strong> ' . $examples . '</p>';
        }

        return implode('', $parts);
    }
}

