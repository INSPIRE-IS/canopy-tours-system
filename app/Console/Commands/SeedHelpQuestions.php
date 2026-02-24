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
            $formattedAnswer = $this->formatAnswer($description, $where, $businessRules);

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
                'business_rules' => 'Permissions are managed on roles, not users.',
                'is_internal' => true,
            ],
            [
                'category' => 'Permissions',
                'question' => 'Why can I not see a menu item?',
                'description' => 'Permissions control access to menus and actions.',
                'where' => '<a href="/admin/permissions">Admin > Permissions</a>',
                'business_rules' => 'Permissions are assigned to roles and enforced by gates.',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesalers',
                'question' => 'When should I use a wholesaler booking?',
                'description' => 'Wholesalers are trade partners with contracted rates.',
                'where' => '<a href="/admin/wholesalers">Admin > Wholesalers</a>',
                'business_rules' => 'Wholesaler rates override standard rates when applied.',
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
                'business_rules' => 'Capacity is enforced on bookings; duration drives time range.',
                'is_internal' => true,
            ],
            [
                'category' => 'Seasons',
                'question' => 'Why is a date not bookable?',
                'description' => 'Seasons control availability dates for services.',
                'where' => '<a href="/admin/seasons">Admin > Seasons</a>',
                'business_rules' => 'Bookings must fall within season dates; questionnaires are seeded on create.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How are time slots generated?',
                'description' => 'Inventory defines dates and time slots per season.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a>',
                'business_rules' => 'Time slots drive availability and capacity calculations.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory Overrides',
                'question' => 'When do we use an inventory override?',
                'description' => 'Override base rates for a specific inventory.',
                'where' => '<a href="/admin/inventories-overrides">Admin > Inventory Overrides</a>',
                'business_rules' => 'Allowed only when season.allow_internal_price_override is true. Overrides clear promos/discounts/wholesaler pricing.',
                'is_internal' => true,
            ],
            [
                'category' => 'Closeouts',
                'question' => 'How do we block a day or slot?',
                'description' => 'Closeouts block dates or time slots from booking.',
                'where' => '<a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Closed slots are unavailable in admin and front calendars.',
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
                'business_rules' => 'Discounts are separate from promo codes.',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Why is a promo not selectable?',
                'description' => 'Promo codes use booking and activity windows.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Booking window uses today; activity window uses booking date. Admin sees all; front only active and internal_use_only=false.',
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
                'business_rules' => 'At least 1 adult or 1 child is required. Capacity is enforced; overrides allowed only when service permits.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Edit',
                'question' => 'Why does editing reissue invoices?',
                'description' => 'Edit bookings and reissue documents.',
                'where' => 'Open a booking from <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Edits reissue invoice and boarding pass. Expired seasons are included for the current booking.',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'How do we record a manual payment?',
                'description' => 'Record manual or simulated payments.',
                'where' => 'Open a booking > Payments or <a href="/admin/booking-payments/create/BOOKING_ID">/admin/booking-payments/create/BOOKING_ID</a>',
                'business_rules' => 'Totals are VAT-inclusive; simulated payments store ex-VAT and VAT amounts.',
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
                'business_rules' => 'Totals are VAT-inclusive; VAT is shown as a breakdown line.',
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
                'category' => 'Check-in',
                'question' => 'How do we send a check-in link?',
                'description' => 'Check-in links are signed and shared with guests.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Signed links required; questionnaires auto-seed if missing.',
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
                'business_rules' => 'Links are signed and available after activity dates.',
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
                'business_rules' => 'Availability is based on booked pax; edit calendar includes existing booking pax in capacity checks.',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'Where are VAT rates configured?',
                'description' => 'System settings and lookups.',
                'where' => 'Admin > Settings (varies by module).',
                'business_rules' => 'VAT rates are configured in Lookups > VAT Rate.',
                'is_internal' => true,
            ],
        ];
    }

    protected function formatAnswer(string $description, string $where = '', string $businessRules = ''): string
    {
        $description = trim($description);
        $where = trim($where);
        $businessRules = trim($businessRules);

        $parts = [];
        $parts[] = '<p><strong>Description:</strong> ' . ($description !== '' ? $description : 'N/A') . '</p>';
        $parts[] = '<p><strong>Where:</strong> ' . ($where !== '' ? $where : 'N/A') . '</p>';
        $parts[] = '<p><strong>Business Rules:</strong> ' . ($businessRules !== '' ? $businessRules : 'N/A') . '</p>';

        return implode('', $parts);
    }
}
