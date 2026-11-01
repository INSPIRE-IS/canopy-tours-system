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
    protected $description = 'Seed help categories and the maintained baseline knowledge-base articles.';

    public function handle(): int
    {
        $this->info('Seeding help question categories...');

        $adminOnly = [1, 2];
        // Management roles that can configure sites (including publish/unpublish).
        $siteAdmins = [1, 2, 3];
        $categories = [
            ['name' => 'General', 'role_ids' => null],
            ['name' => 'Sites (Teams)', 'role_ids' => $siteAdmins],
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
                'name' => $category['name'],
            ], [
                'name' => $category['name'],
                'order_id' => $index + 1,
                'role_ids' => $category['role_ids'],
            ]);
        }

        $this->info('Categories seeded: '.count($categories));

        if (! $this->option('with-samples')) {
            $this->info('No help articles seeded. Use --with-samples to add or update the maintained baseline articles.');

            return self::SUCCESS;
        }

        $teamId = $this->option('team');
        if ($teamId !== null && ! Team::whereKey($teamId)->exists()) {
            $this->error('Team not found for --team='.$teamId);

            return self::FAILURE;
        }

        $questions = $this->sampleQuestions();
        foreach ($questions as $item) {
            $category = TypeHelpQuestion::where('name', $item['category'])->first();
            if (! $category) {
                $this->warn('Skipping question, missing category: '.$item['category']);

                continue;
            }

            $description = $item['description'] ?? $item['answer'] ?? '';
            $where = $item['where'] ?? '';
            $businessRules = $item['business_rules'] ?? '';
            $examples = $item['examples'] ?? '';
            $formattedAnswer = $this->formatAnswer(
                $description,
                $where,
                $businessRules,
                $examples,
                $item['related'] ?? ''
            );

            // Avoid duplicate sample questions when we normalize wording/quotes over time.
            // The question string is the natural key in updateOrCreate, so we also try to
            // match a legacy variant with curly quotes if it exists.
            $questionText = $item['question'];
            $legacyQuestionText = $this->legacyQuestionVariant($questionText);

            $question = HelpQuestion::query()
                ->where('type_help_question_id', $category->id)
                ->where('team_id', $teamId)
                ->where(function ($q) use ($questionText, $legacyQuestionText) {
                    $q->where('question', $questionText);
                    if ($legacyQuestionText !== $questionText) {
                        $q->orWhere('question', $legacyQuestionText);
                    }
                })
                ->first();

            if ($question) {
                $question->update([
                    'question' => $questionText,
                    'is_internal' => (bool) ($item['is_internal'] ?? false),
                ]);
            } else {
                $question = HelpQuestion::create([
                    'question' => $questionText,
                    'type_help_question_id' => $category->id,
                    'team_id' => $teamId,
                    'is_internal' => (bool) ($item['is_internal'] ?? false),
                ]);
            }

            HelpAnswer::updateOrCreate(
                ['help_question_id' => $question->id],
                ['answer' => $formattedAnswer]
            );
        }

        $this->info('Sample help questions seeded: '.count($questions));

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
                'category' => 'General',
                'question' => 'Why do I see "Site under construction" on the front welcome page?',
                'description' => 'This happens when there are no published venues available on the public site.',
                'where' => '<a href="/front/welcome">Front > Welcome</a>, <a href="/admin/teams">Admin > Site</a>',
                'business_rules' => 'The active public entry uses Booking V2. Only teams with Publish enabled are offered. One published site opens directly; zero or multiple published sites show the V2 site-selection experience, with the zero-site state explaining that booking is not yet available. The legacy welcome route remains a fallback and should not be used as the normal public link. Publishing a site requires <code>teams_publish</code> permission.',
                'related' => 'sites (teams), publish, teams_publish, front welcome',
                'is_internal' => true,
            ],
            [
                'category' => 'General',
                'question' => 'Which public booking route should we use?',
                'description' => 'Booking V2 is the active public customer flow; the legacy route is retained only as a fallback.',
                'where' => '<a href="/front/welcome">Front > Welcome</a>',
                'business_rules' => 'Use <code>/</code> or <code>/front/welcome</code> as the normal public entry. The application selects or asks for a published site and continues through the V2 service, availability, checkout, gift-voucher, and payment-result screens. Routes under <code>/front/legacy</code> belong to the previous public flow and must not be used for new website booking links unless a deliberate rollback is required.',
                'related' => 'Booking V2, public welcome, published sites, legacy fallback',
                'is_internal' => true,
            ],
            [
                'category' => 'General',
                'question' => 'Why does the admin dashboard show data for the wrong site?',
                'description' => 'The dashboard is team-scoped to the currently selected team in your session.',
                'where' => '<a href="/admin">Admin > Dashboard</a>',
                'business_rules' => 'Dashboard widgets (monthly bookings chart, latest bookings, notifications, customer manifests) are filtered by the selected <code>team_id</code> stored in session (fallback: your user team). If data looks wrong, confirm you have the correct team selected.',
                'related' => 'dashboard, team selection, session team_id, multi-tenant',
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
                'category' => 'Sites (Teams)',
                'question' => 'How do I publish/unpublish a site on the frontend?',
                'description' => 'Publishing controls whether a site appears on the public front-end venue dropdown and whether direct access is allowed.',
                'where' => '<a href="/admin/teams">Admin > Site</a> (Edit a site > Publish checkbox)',
                'business_rules' => 'Only users with the <code>teams_publish</code> permission can change publish status (by default: Admin/Director/Manager). Unpublished sites are hidden from the public venue list. Direct access to <code>/front/welcome/&lt;team_id&gt;</code> returns 404 for public users. Logged-in staff with <code>teams_publish</code> can still view unpublished sites for setup. If a public user has a previously saved venue in browser storage and the venue is later unpublished, the app clears the saved venue and prompts for a new selection.',
                'related' => 'permissions, sites_access, teams_publish',
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
                'category' => 'System / Settings',
                'question' => 'How do I reset a site/team to a clean slate (remove staging data)?',
                'description' => 'Use the site reset command to wipe operational and setup data for a team, while keeping teams and users.',
                'where' => '<code>php artisan site:reset</code> (server / CLI)',
                'business_rules' => 'This is destructive and should only be run by Admin users on staging/test (or with a production backup plan). The command keeps <strong>teams</strong> and <strong>users</strong>, but deletes the team\'s operational and setup data so you can reconfigure the site from scratch. Use <code>--dry-run</code> first to review counts. When not using dry-run, you must type an explicit confirmation string (e.g. <code>RESET TEAM 3</code> or <code>RESET ALL TEAMS</code>).',
                'examples' => '<p><strong>Preview only</strong>: <code>php artisan site:reset --team=3 --dry-run</code></p><p><strong>Reset one team</strong>: <code>php artisan site:reset --team=3</code></p><p><strong>Preview all teams</strong>: <code>php artisan site:reset --all --dry-run</code></p>',
                'related' => 'teams, users, bookings, invoices, payments, manifests, services, seasons, inventory, closeouts, discounts, promotions, wholesalers, audit logs',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'What exactly does the site reset command delete?',
                'description' => 'The site reset command removes operational + configuration data for a team and cleans up related files and audit logs.',
                'where' => '<code>php artisan site:reset</code> (see: <code>app/Console/Commands/SiteResetCommand.php</code>)',
                'business_rules' => 'For the selected team, it deletes: alerts/notifications, booking data (bookings, booking items, inventories, fees, payments, notes, emails, invoices), manifests (customer_manifests, booking_items_customer, booking_staff, booking_details/infos), questionnaire answers (question_answers, booking_questionnaires, and legacy questionnaire_answers), wholesaler booking links (wholesaler_bookings), gateway logs, and audit logs for wiped entities. It also deletes setup/config: services, seasons, inventories + time slots + type days, inventory overrides + slot ranges, closeouts (and closeout_day_time_slots), promotions (and promotion_day_time_slots), vouchers, discounts (and discount_services), wholesalers (and wholesaler_contacts/wholesaler_rates), gift voucher items, and booking vouchers. It deletes related file rows (including booking-linked files) and then removes the physical files from <code>storage/app/public</code> on a best-effort basis. Finally it deletes <em>orphaned</em> customers that are no longer referenced by any remaining records. It does <strong>not</strong> delete teams, users, or templates.',
                'examples' => 'If you reset team 3, all bookings/services/seasons for team 3 are removed. Users remain, so staff can log in and rebuild the setup.',
                'related' => 'site reset, destructive, staging, backup, orphan cleanup, files, audit logs',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesalers',
                'question' => 'When should I use a wholesaler booking?',
                'description' => 'Wholesalers are trade partners with contracted rates.',
                'where' => '<a href="/admin/wholesalers">Admin > Wholesalers</a>',
                'business_rules' => 'Wholesaler rates override standard rates when a booking is marked as wholesaler. Rates are matched by wholesaler + service + season + activity date. If no valid rate exists, staff must add/update a rate or recalculate from rack.',
                'related' => 'wholesaler rates, booking pricing, discounts, promotions, service, season, date range',
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
                'business_rules' => 'Rates apply only when a booking is marked as wholesaler. Each rate can have a name and effective date range. New bookings use the active rate valid for the selected activity date. Existing bookings keep the actual rate values saved on the booking item, so later rate edits do not change old invoices or reports.',
                'related' => 'wholesaler, booking pricing, service, season, discounts, promotions, date range',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesaler Rates',
                'question' => 'What happens when a wholesaler rate changes?',
                'description' => 'Create a new named/date-ranged rate instead of overwriting old booking history.',
                'where' => '<a href="/admin/wholesaler-rates">Admin > Wholesaler Rates</a> and Admin booking create/edit',
                'business_rules' => 'Use named/date-ranged rates for rate changes. Do not overwrite old captured bookings automatically. A booking stores the selected wholesaler_rate_id plus the actual adult/child/guide rates and calculated fee totals used at the time. If an old booking truly needs the new rate, staff must edit that booking and recalculate/save deliberately. This is safer for invoices, audit logs, Franchise Fees, and Wholesaler Usage reports.',
                'examples' => 'If Operator A moves from R600 to R650 from 1 September, keep the old rate ending 31 August and add a new Operator A September rate starting 1 September. August bookings remain R600; September bookings default to R650.',
                'related' => 'wholesaler rates, date ranges, booking edit, audit, reports, Franchise Fees, Wholesaler Usage',
                'is_internal' => true,
            ],
            [
                'category' => 'Wholesaler Rates',
                'question' => 'How do prepaid voucher partner rates work?',
                'description' => 'Use prepaid voucher partner rates for external voucher/platform bookings where the guest has already paid the partner.',
                'where' => '<a href="/admin/wholesaler-rates">Admin > Wholesaler Rates</a> and Admin booking create/edit',
                'business_rules' => 'Create the partner as a wholesaler, then add a wholesaler rate for the relevant service and season. Tick Prepaid Voucher Partner only on rates where the guest has already paid an external platform or voucher partner. Staff should book the guest using the actual partner/reporting rate, not R0, unless the true reportable value is zero. The booking keeps the rate value for reporting and invoice value, but marks the customer balance as paid so Total Due Today is R0.',
                'examples' => 'If a partner sells a guest voucher for R600 per adult, set the wholesaler adult rate to R600 and tick Prepaid Voucher Partner. A 2-adult booking keeps R1,200 as the booking/reporting value, but the guest balance due is R0 because the guest already paid the partner.',
                'related' => 'wholesaler, wholesaler rates, prepaid voucher, voucher partner, Hyperli, Daddy\'s Deals, booking pricing, Franchise Fees, paid balance',
                'is_internal' => false,
            ],
            [
                'category' => 'Services',
                'question' => 'Where is capacity and duration set?',
                'description' => 'Services define capacity, duration, and base pricing.',
                'where' => '<a href="/admin/services">Admin > Services</a>',
                'business_rules' => 'Capacity is enforced on bookings; duration drives the time-range displayed on calendars. Services also use Online Bookable to control whether the service appears on the public booking flow.',
                'examples' => 'If a service capacity is 8, bookings cannot exceed 8 unless Override Capacity is enabled for that service and selected on the booking.',
                'related' => 'seasons, inventory, capacity, bookings, time slots, override capacity, online bookable',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'How does Online Bookable work?',
                'description' => 'Online Bookable controls whether a service appears on the public booking flow.',
                'where' => '<a href="/admin/services">Admin > Services</a>',
                'business_rules' => 'Online Bookable on = guests can see and book the service online when its season/inventory also allows availability. Online Bookable off = the service is staff-only: guests cannot see/open it on the public frontend, but staff can still use it in admin workflows. Season-level internal-only support remains in the backend for future use but is not exposed in the normal Season UI.',
                'examples' => 'For a team-build service that should only be sold by staff, turn Online Bookable off on the service.',
                'related' => 'services, online booking, staff only, internal only, seasons, date overrides',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'Gift services vs bookable services (what shows where?)',
                'description' => 'Online Bookable controls public booking visibility; gift vouchers remain a separate flow.',
                'where' => '<a href="/admin/services">Admin > Services</a> and <a href="/front/welcome">Front > Welcome</a>',
                'business_rules' => 'Online Bookable controls whether a service can appear in the public Booking V2 flow. When disabled, the service remains available to authorised staff but is hidden from guests. Buying the activity as a gift is a separate date-free checkout and does not reserve dated capacity; however, the current public gift route still requires the site to be published and the selected service to be Online Bookable. There is no separate public Giftable visibility switch in V2.',
                'examples' => 'A staff-managed product can be Online Bookable off while still being available to internal users in Admin.',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'How do I clone a service?',
                'description' => 'Cloning creates a full copy of the service and its related setup.',
                'where' => 'Open a service detail view from <a href="/admin/services">Admin > Services</a> and click Clone Service.',
                'business_rules' => 'Clone copies service fields, seasons, inventories, time slots, inventory days, and closeouts. The clone starts as Draft/hidden. Review its copied content, seasons, inventory, pricing, and availability before enabling Online Bookable. The owning site must also be published before the service can appear publicly.',
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
                'related' => 'inventory, closeouts, date overrides, availability, questionnaires',
                'is_internal' => true,
            ],
            [
                'category' => 'Services',
                'question' => 'How do staff-only services work?',
                'description' => 'A service can be hidden from public online booking while remaining available to staff in admin workflows.',
                'where' => '<a href="/admin/services">Admin > Services</a>',
                'business_rules' => 'Use the Online Bookable service flag for specialty services such as team builds or staff-managed offerings. When Online Bookable is off, guests cannot see or directly open that service on the public frontend. Logged-in staff/admin can still manage the service and create bookings internally. This is different from an internal-only Date Override: the service flag hides the full service online, while a Date Override internal-only flag hides only selected slots.',
                'examples' => 'If "The Fear Factor" should only be sold by staff, edit the service and turn Online Bookable off. The product will be unavailable to guests online but staff can still manage bookings internally.',
                'related' => 'services, online booking, internal only, date overrides, staff bookings, team builds',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How are time slots generated?',
                'description' => 'Inventory defines dates and time slots per season.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a>',
                'business_rules' => 'Weekly day selections + time slots generate availability across a season. Closeouts remove specific days/times from availability.',
                'examples' => 'If a season runs 1-31 May and you select Mondays + Wednesdays with 09:00 and 13:00, those two slots appear on every Monday and Wednesday in May. To block 13:00 on May 15, add a closeout for that date/time.',
                'related' => 'seasons, closeouts, date overrides, availability, time slots',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'What is the difference between inventory and closeouts?',
                'description' => 'Inventory creates availability; closeouts remove it.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a> and <a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Inventory defines the recurring weekly schedule for a season. Closeouts are exceptions that block specific dates/times without changing the base schedule.',
                'examples' => 'If Fridays at 10:00 are available all season, add a closeout for 2026-06-12 at 10:00 to block only that one slot.',
                'related' => 'inventory, closeouts, date overrides, availability',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How can I preview the final availability calendar?',
                'description' => 'Use Preview Availability to see the effective calendar after all availability rules are applied.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a>, <a href="/admin/inventory-slot-ranges">Admin > Date Overrides</a>, or <a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Preview Availability is read-only. It shows the final result after base inventory, closeouts, date overrides, capacity overrides, admin-only/internal-only flags, and bookings. Use it after changing Inventory, Date Overrides, or Closeouts to confirm what customers and staff will see before relying on the setup. Date Overrides still take precedence for explicit override slots.',
                'examples' => 'After adding a holiday Date Override or a winter Closeout, click Preview Availability and choose the service/season. Green slots are online-open, amber slots are admin-only, red slots are closed, orange slots are full, and grey slots are not available.',
                'related' => 'availability preview, inventory, closeouts, date overrides, internal only, admin-only, capacity',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'Where does the current inventory model fall short?',
                'description' => 'Weekly schedules expand across the full season, which can be too broad for date-specific needs.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a> and <a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Recurring inventory still defines the normal weekdays and time slots across a season. For exceptional exact dates, use Date Overrides or Inventory Schedule instead of creating closeouts for every unwanted matching weekday. A public Date Override can deliberately open or capacity-adjust an exact date/time; an internal-only override remains staff-only. Use Preview Availability to confirm the effective result.',
                'examples' => 'If 09:00 should open only on selected dates, configure the normal season/inventory carefully and use <a href="/admin/inventory-slot-ranges">Admin > Date Overrides</a> or Inventory Schedule for those exact cells. Use a closeout when an otherwise recurring slot must be closed.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How does slot funneling work?',
                'description' => 'Slot funneling limits how many qualifying departures are shown as open at one time.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a> (Slot Funneling)',
                'business_rules' => 'Slot funneling is configured on the inventory for a specific service and season. Set the maximum number of open slots to zero to disable it, or choose how many chronological qualifying slots may remain open. Full or insufficient-capacity earlier slots do not consume an open position, so later qualifying slots can open. The shared calendar builder applies the same rule to admin availability and Booking V2. This is a chronological max-open-slots rule, not automatic demand-based price changing.',
                'examples' => 'With a maximum of 2 open slots, the first two qualifying departures are available. As an earlier departure becomes full for the requested group size, the next qualifying departure can open.',
                'related' => 'inventory, seasons, availability, capacity, Booking V2, demand pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How do Date Overrides work?',
                'description' => 'Date overrides replace the weekly schedule for specific dates without changing the base inventory.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Weekly schedule is the default. Closeouts remove normal availability. Custom/one-off slots must be created via Date Overrides, which take precedence over the weekly schedule for those dates and over closeouts for the specific overridden slots. If a date override exists for a date, only those override slots are used for that date. Overrides can use custom times not present in weekly inventory. Internal-only overrides show on admin calendars only. Use Date Overrides for exact exceptions, capacity changes, admin-only slots, or reopening a specific date/time that a closeout would otherwise block.',
                'examples' => 'Weekly schedule: Mon/Wed/Fri 09:00 + 13:00. Date override: May 5-7 with only 10:00. Result: May 5-7 shows only 10:00; all other Mon/Wed/Fri still show 09:00 + 13:00. If you mark the override internal-only, it appears in admin calendars but not on the front booking calendar. If a public-holiday Monday is normally closed by a closeout, create a Date Override for that Monday and select the slots that should reopen.',
                'related' => 'inventory, closeouts, availability, capacity override, internal only',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'When should I use Date Overrides?',
                'description' => 'Use date overrides when you need custom availability for a specific date range without changing the weekly schedule.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Overrides apply only to the selected dates and replace the weekly schedule for those dates. Override-only slots are hidden on other dates. Internal-only overrides show on admin calendars only. If a capacity override is set, that capacity is used for those override slots. Date Overrides are the final exception layer, so an explicit override slot can reopen a slot that is normally blocked by a closeout. Closeouts should shape the normal closed pattern; Date Overrides should handle exceptions.',
                'examples' => 'If your weekly schedule is 09:00 + 13:00 but you need 12:00 + 14:00 on Mar 23-25, add a date override for those dates with 12:00 + 14:00. Those dates show only 12:00 + 14:00; the rest of the season remains 09:00 + 13:00. If you set capacity override = 6, those slots cap at 6 seats even if the service capacity is higher.',
                'related' => 'inventory, closeouts, availability, capacity override, internal only',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'Can I include default weekly slots in a date override?',
                'description' => 'Yes. Date Overrides can prefill the weekly inventory slots so you only remove what you don\'t want.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Use the "Include default weekly slots" toggle to copy the current weekly time slots into the override list. You can then remove any slots you want to exclude for that date range. Turning the toggle off removes the default slots from the list.',
                'examples' => 'If the weekly slots are 08:00 and 09:00, enabling Include Defaults adds both. You can remove 08:00 to keep only 09:00 for those dates.',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How do I hide a time slot from online bookings but keep it for staff?',
                'description' => 'Use an internal-only Date Override so the slot shows on admin calendars but not on the guest calendar.',
                'where' => '<a href="/admin/inventory-slot-ranges">Admin > Inventory > Date Overrides</a>',
                'business_rules' => 'Internal-only overrides are excluded from the front (guest) calendar, but included for logged-in admin/staff. Closeouts block normal admin and online availability, but explicit Date Override slots can reopen a closed slot. If a date override exists for a date, only those override slots are used for that date (weekly slots are replaced for that date).',
                'examples' => '<p><strong>Example 1 (Monday closed, but open in holidays)</strong>: Keep Mondays closed in weekly inventory. For school-holiday Mondays, add a Date Override for the holiday date range with the slots you want and mark them internal-only until you\'re ready to open them online.</p><p><strong>Example 2 (short-staffed Tuesdays)</strong>: Weekly inventory has 08:30, 10:00, 11:30. For a specific Tuesday where only one team is available, create a Date Override for that date with only 08:30 and 10:00. If you want staff-only booking for the extra slot, add it as internal-only.</p>',
                'related' => 'date overrides, internal only, online calendar, admin calendar, closeouts, weekly schedule',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'How should I use the Inventory Schedule tool?',
                'description' => 'Use Schedule from an Inventory row as the main working view for day-to-day availability changes.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a> (click Schedule on an inventory row)',
                'business_rules' => 'Inventory is the broad/default service + season schedule. The Schedule tool shows the effective calendar after base inventory, date overrides, closeouts, bookings, admin-only flags, and capacity overrides. Use Schedule to close slots, open exception slots, mark slots admin-only, set capacity overrides, and clear scheduler-created changes. Existing bookings stay visible and booked-slot changes require confirmation.',
                'examples' => '<p><strong>KCT example</strong>: keep the base inventory broad, then use Schedule filters to close unwanted weekday slots, open a public-holiday Monday, or make a slot admin-only for staff bookings.</p><p><strong>Admin-only</strong>: tick Admin only when opening or capacity-adjusting a slot so it remains available to staff but hidden from the public calendar.</p>',
                'related' => 'inventory, schedule, availability, closeouts, date overrides, internal only, capacity override, booked slots',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory',
                'question' => 'Why do scheduler changes appear in Date Overrides and Closeouts?',
                'description' => 'The Schedule tool uses existing Date Override and Closeout tables behind the scenes so the availability engine remains consistent.',
                'where' => '<a href="/admin/inventories">Admin > Inventory</a>, <a href="/admin/inventory-slot-ranges">Admin > Date Overrides</a>, <a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Closing slots creates closeout records. Opening slots, admin-only visibility, and capacity overrides create date override records. Scheduler-created records use a [Scheduler] prefix. Rose and other operators should normally use the Schedule tool rather than manually editing generated technical records. Clear scheduler adjustment only removes scheduler-created changes; manual records are left untouched.',
                'examples' => 'If a Monday closeout covers June to August and Schedule opens one public-holiday Monday slot, the closeout may be split into before/after records so the rest of the period stays closed. This is expected technical data.',
                'related' => 'inventory schedule, date overrides, closeouts, scheduler, [Scheduler], availability',
                'is_internal' => true,
            ],
            [
                'category' => 'Inventory Overrides',
                'question' => 'When do we use an inventory override?',
                'description' => 'Override base rates for a specific inventory.',
                'where' => '<a href="/admin/inventories-overrides">Admin > Inventory Overrides</a>',
                'business_rules' => 'Allowed only when season.allow_internal_price_override is true. Overrides clear promos/discounts/wholesaler pricing for that booking.',
                'examples' => 'If a private group needs a custom price on 10 June at 09:00, create an inventory override for that inventory record. The booking will use the override price and will ignore promos/discounts/wholesaler rates.',
                'related' => 'inventory, overrides, pricing, promotions, discounts, wholesalers',
                'is_internal' => true,
            ],
            [
                'category' => 'Closeouts',
                'question' => 'How do we block a day or slot?',
                'description' => 'Closeouts block dates or time slots from booking.',
                'where' => '<a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Closed slots are unavailable in admin and front calendars unless an explicit Date Override reopens that exact date/time. Closeouts can target specific weekdays and time slots over a date range. Closeouts may overlap because two closeouts still mean the slot is closed. Priority controls which closeout reason appears first in Preview Availability; it does not change open/closed availability. At least one day/time cell must be selected when saving a closeout. To reopen an entire closeout period, use Remove on the closeout row instead of editing it to no selected slots.',
                'examples' => 'If you select a date range 10-14 July, weekdays Mon-Fri, and time slot 09:00, then 09:00 is blocked on every weekday in that range. To close all Mondays and Tuesdays for a season, set the closeout date range to the full season and tick all Monday/Tuesday time slots that should be closed. If Christmas falls inside that range and also needs a specific closeout reason, create a Christmas closeout with higher priority. The slot stays closed either way, but Preview Availability shows the Christmas reason first.',
                'related' => 'inventory, date overrides, availability, time slots, seasons, overlaps',
                'is_internal' => true,
            ],
            [
                'category' => 'Closeouts',
                'question' => 'How do I check or fix overlapping closeouts?',
                'description' => 'Use Check Overlaps to find closeouts that block the same service, season, date range, weekday, and time slot.',
                'where' => '<a href="/admin/closeouts">Admin > Closeouts</a>',
                'business_rules' => 'Overlapping closeouts are allowed. Use Check Overlaps as a diagnostic tool to find duplicate/redundant closeout coverage, not as a blocker. Higher priority closeouts are displayed first in Preview Availability so support/admin can see which rule is intended to explain the closure. Date Overrides remain the correct way to reopen or capacity-adjust a specific exception date/time.',
                'examples' => 'If Winter closes Monday 09:00 from June to August and Holiday also closes Monday 09:00 inside that period, the slot is simply closed. Set Holiday to a higher priority if you want Preview Availability to show Holiday as the main reason. If that Monday should be open during school holidays, add a Date Override for that Monday and choose the slots/capacity to open.',
                'related' => 'closeouts, overlaps, date overrides, availability preview, inventory',
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
                'related' => 'promotions, discounts, promo codes, booking pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'What is the difference between discounts and promos?',
                'description' => 'Discount rules applied to bookings or invoices.',
                'where' => '<a href="/admin/discounts">Admin > Discounts</a>',
                'business_rules' => 'Discounts are separate from promo codes. Discounts can be per-line (per pax) or invoice-level. Per-line discounts reduce individual rates; invoice-level discounts apply to the total after all per-line logic. Discounts do not have booking/activity windows.',
                'examples' => 'A 10% line discount for Adults reduces adult rates only. A 10% invoice discount reduces the full total after line-level discounts are applied.',
                'related' => 'promotions, promo codes, booking pricing, invoice discounts, line discounts',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'What are line vs invoice discounts?',
                'description' => 'Line discounts apply to specific pax types; invoice discounts apply to the total.',
                'where' => '<a href="/admin/discounts">Admin > Discounts</a> and Admin booking create/edit',
                'business_rules' => 'Line discounts reduce selected adult and/or child pax counts entered on the booking form. The booking form exposes separate Discount PAX (adult) and Discount PAX (child) fields so staff can discount only the matching guests. Invoice discounts apply once to the total after line discounts. Only one invoice discount should be applied at a time.',
                'examples' => 'If a booking has 2 Adults + 1 Child and you apply a 15% line discount with Adult PAX 1 and Child PAX 1, one adult and one child are discounted while the remaining adult stays at rack rate.',
                'related' => 'discounts, invoice discount, line item, promotions, booking totals',
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
                'examples' => 'If a promo code is applied to a booking, line discounts (e.g., "Local Resident 10%") will not apply. An invoice-level discount can still be applied if selected.',
                'is_internal' => true,
            ],
            [
                'category' => 'Discounts',
                'question' => 'Why is a discount not showing on a booking?',
                'description' => 'Discount visibility depends on service and context.',
                'where' => 'Admin booking create/edit',
                'business_rules' => 'Discounts only appear for services they are linked to. If a promotion is selected, per-line discounts are not applied. Invoice discounts are exclusive and apply only when selected.',
                'examples' => 'If a discount is linked to "Canopy Tour A" but the booking is for "Canopy Tour B", it will not show. If a promo code is applied, the discount list may be empty.',
                'related' => 'promotions, promo codes, service, booking pricing, line discounts',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Why is a promo not selectable?',
                'description' => 'Promo codes use booking and activity windows.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Booking window uses today; activity window uses the booking date (activity date). Admin sees all; front only shows active promos with internal_use_only=false. If the activity date is outside the promo activity window, the promo will be invalid. Typing in the promo-code field does not validate each letter; staff must click Apply or select a promo from the info icon before the promo is applied.',
                'examples' => 'If a promo\'s booking window is Feb 1-Feb 28 and today is March 3, it will be expired (not selectable). If today is within the booking window but the activity date is May 5 and the promo activity window is April only, it will show as invalid. If a typed code fails on Apply, use the info icon to confirm the available promo names/codes.',
                'related' => 'promo codes, booking window, activity window, discounts, booking pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'How do booking dates vs activity dates work for promos?',
                'description' => 'Promos have two date ranges: booking window and activity window.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Booking window controls when the promo can be used (today must be within booking range). Activity window controls which activity dates are eligible (booking date must fall within activity range). Booking dates are not tied to seasons. General promotions can span multiple eligible seasons for the selected service when both activity-window boundary dates are covered; gaps remain unavailable because the calendar still requires real season inventory. Time-slot promotions remain restricted to their selected season because slot IDs are inventory-specific.',
                'examples' => 'Booking window: Feb 1-Feb 28. Activity window: May 1-May 31. You can book in February for a May activity date. Booking in March won\'t allow the promo even if the activity date is in May. A long-running percent promo can start in one public season and end in a later public season; a time-slot promo cannot cross that boundary.',
                'related' => 'promo codes, booking window, activity window, seasons, discounts',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'What promotion types are available?',
                'description' => 'Promotions can change pricing by percent, fixed value, tiers, or time slots.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Types include percent, fixed value, varied child, volume discount, exact pax discount, and time-slot promos. Graduated promos are legacy/admin-maintained only and are kept for existing records. Fixed value supports Apply To modes for booking total, one adult, one child, each adult, each child, and each adult + child. Exact pax promos apply only to configured pax counts using % or R logic. Time-slot promos apply only to selected days/time slots.',
                'examples' => '
                 

<strong>* Percent Fields</strong>: <strong>Value </strong>(%). <strong>Logic</strong>: Percent off booking total. A &ldquo;10% off&rdquo; promo reduces each pax rate by 10%. Fixed Value <strong>Fields</strong>: Value (R). <strong>Logic</strong>: Fixed amount off booking total. <strong>* Exact Pax Discount Fields:</strong> Pax rows with % or R values. <strong>Logic</strong>: Applies only when the booking pax exactly matches a configured row. <strong>* Varied Child Fields:</strong> Min adults + Child discount %, or tiers. <strong>Logic</strong>: Child-only discount when min adults/tier matched. <strong>* Volume Discount Fields:</strong> Tiers (pax threshold, type, value). <strong>Logic</strong>: Best tier applies; percent or fixed value off booking total. <strong>* Time Slot Fields:</strong> Season, Days, optional Time Slots, <strong>Value </strong>(%). <strong>Logic</strong>: Percent off booking total for selected days; no slots = all slots. <strong>* Graduated</strong>: legacy exact-pax final-total pricing kept for existing records only. -- <em>Day/time filters: if no days are selected, the promo applies to all days. If days are selected and no time slots are checked, it applies to every slot for those days.</em></p>',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Fixed value Apply To options (how do they work)?',
                'description' => 'Fixed value promos can subtract once from the booking, once from a pax type, or per matching pax.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Promotion Edit/Create > Type: Fixed Value > Apply To)',
                'business_rules' => 'Booking total subtracts the value once from the full booking and is best for package specials. One adult or One child subtracts the value once from that pax type and requires that pax type to be present; this is best for birthday-style promos. Each adult/child modes multiply the value by the matching pax count. Pax-specific discounts are capped at that pax type subtotal and do not spill over to other pax types.',
                'examples' => 'Birthday child: Fixed Value R795, Apply To One child. Birthday adult: Fixed Value R895, Apply To One adult. Valentine package: Fixed Value R800, Apply To Booking total.',
                'related' => 'promotions, fixed_value, pricing, booking total, birthday promo, pax target',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Percent promotion (how does it apply)?',
                'description' => 'Percent promos reduce the booking total by a percentage and are applied across pax rates.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Percent)',
                'business_rules' => 'Percent promos apply when today is within the booking window and the activity date is within the activity window. The percent is applied to adult/child/guide rates (and totals are rounded to 2 decimals).',
                'examples' => 'Example 1: 10% off on a R1000 booking => total becomes R900. Example 2: 15% off with 2 adults at R500 => each adult becomes R425 (rounded), total R850.',
                'related' => 'promotions, percent, booking window, activity window, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Fixed value (booking-total) promotion (how does it apply)?',
                'description' => 'Fixed value (Apply To: Booking total) subtracts a fixed Rand amount once from the booking total.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Fixed Value > Apply To: Booking total)',
                'business_rules' => 'The value is subtracted once per booking (not per guest). The discount is distributed proportionally across adult/child/guide rates so totals remain consistent.',
                'examples' => 'Example 1: Value R200 on a R3200 booking => total becomes R3000. Example 2: Value R50 on a 1-adult booking at R600 => total becomes R550.',
                'related' => 'promotions, fixed_value, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Fixed value one-adult or one-child promotion (how does it apply)?',
                'description' => 'One adult/child fixed value promos subtract once from the selected pax type.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Fixed Value > Apply To: One adult or One child)',
                'business_rules' => 'The selected pax type must exist on the booking. The value is subtracted once from that pax type subtotal and is capped at zero. It does not spill over to other pax types; if the discount should reduce the whole booking, use Booking total instead.',
                'examples' => 'Example 1: Birthday child promo R795, one child at R895 => child subtotal becomes R100. Example 2: R895 One adult promo with no adults will be rejected until an adult is added.',
                'related' => 'promotions, fixed_value, per pax, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Graduated promotion (legacy - how does it apply)?',
                'description' => 'Graduated promos are legacy exact-pax promos that override the booking total.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Graduated - legacy, visible only when editing existing graduated promos)',
                'business_rules' => 'The lookup table maps pax count (adults + children) to a target booking total. If there is a match, pricing is forced to that total (line totals are adjusted slightly to match exactly). Use Exact Pax Discount for new exact-pax promos because it supports % and R discount rules.',
                'examples' => 'Example 1: Lookup: 4 pax => R1500; booking has 3 adults + 1 child => total becomes R1500. Example 2: Lookup: 2 pax => R800; booking has 2 adults => total becomes R800. If no lookup exists for the current pax, the promo is rejected instead of applying rack pricing silently.',
                'related' => 'promotions, graduated, pricing, pax lookup',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Varied child promotion (how does it apply)?',
                'description' => 'Varied child promos discount eligible child pricing when an adult minimum is met.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Varied Child)',
                'business_rules' => 'Choose All children to preserve the original threshold rule: once the minimum adult count is met, every child receives the discount. Choose One discounted child per adult for paired specials: the discounted child count is the smaller of the adult and child counts, while additional children remain at rack rate. The child discount can be a percentage or a fixed Rand amount per discounted child and is capped at the child rate.',
                'examples' => 'Paired 50% example: 1 adult + 2 children discounts one child and leaves one child at rack rate; 2 adults + 3 children discounts two children. Rand example: R200 off per paired child with 2 adults + 3 children subtracts R400 from the child subtotal. Legacy all-children example: Min adults=2 with 50% off discounts every child once 2 adults are present.',
                'related' => 'promotions, varied_child, tiers, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Volume discount promotion (how does it apply)?',
                'description' => 'Volume promos apply the best matching tier based on pax count.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Volume Discount)',
                'business_rules' => 'The highest tier where paxCount >= tier.pax is applied. Percent tiers reduce rates by %; fixed value tiers subtract a fixed Rand amount from the booking total (distributed proportionally across rates).',
                'examples' => 'Example 1: Tier 4+ pax = 10%; booking pax 4 => 10% off applies. Example 2: Tier 6+ pax = R300; booking pax 6 => subtract R300 total. If the booking pax is below the first tier, the promo is rejected instead of applying rack pricing silently.',
                'related' => 'promotions, volume_discount, tiers, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Exact pax discount promotion (how does it apply)?',
                'description' => 'Exact pax promos apply only to the pax counts configured on the promo.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Exact Pax Discount)',
                'business_rules' => 'Exact pax uses adults + children only. Each configured pax row can use % off or a fixed Rand amount off the booking total. If the booking pax does not exactly match a configured row, the promo is rejected and staff must recalculate or choose another promo.',
                'examples' => 'Example 1: 1 pax = 10%, 2 pax = 10%; a 2-pax booking gets 10% off. Example 2: the same promo on a 5-pax booking is rejected because no 5-pax rule exists.',
                'related' => 'promotions, pax_exact_discount, exact pax, volume_discount, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Time slot promotion (how does it apply)?',
                'description' => 'Time slot promos apply a percent discount only on selected weekdays and (optionally) selected time slots.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a> (Type: Time Slot)',
                'business_rules' => 'Time slot promos require a season. If days are selected and no time slots are selected, the promo applies to all slots on those days. If specific time slots are selected, it applies only to those slots. Booking window uses today; activity window uses activity date.',
                'examples' => 'Example 1: 20% off on Saturdays (no time slots selected) => all Saturday slots get 20% off. Example 2: 15% off on Mondays at 09:00 only => only Monday 09:00 gets 15% off.',
                'related' => 'promotions, time_slot, weekdays, time slots, pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'How do promotion types affect totals?',
                'description' => 'Each promo type calculates pricing differently.',
                'where' => '<a href="/admin/promotions">Admin > Promotions</a>',
                'business_rules' => 'Percent and fixed-value adjust rates; exact pax discount applies % or R rules only to exact pax matches; varied-child discounts child rates when adult minimum is met; volume tiers apply based on pax thresholds; time-slot promos apply only to selected days/time slots. Graduated is legacy exact-pax final-total pricing and should not be used for new promos.',
                'examples' => 'Varied-child: 2 adults + 1 child with min_adults=2 gives child discount only if at least 2 adults are present. Volume: 4+ pax = 10%. Exact pax: only 1 pax and 2 pax configured means 5 pax receives no promo.',
                'is_internal' => true,
            ],
            [
                'category' => 'Promotions',
                'question' => 'Why do promos and discounts not stack?',
                'description' => 'Promotions override discount logic in pricing.',
                'where' => 'Admin booking create/edit',
                'business_rules' => 'When a promotion is applied, per-line discounts are skipped. This prevents double-discounting. Invoice-level discounts can still apply if explicitly selected.',
                'examples' => 'If a promo is selected, you will not see line discounts apply. To discount further, use the invoice discount selector.',
                'related' => 'discounts, invoice discount, line discounts, booking pricing',
                'is_internal' => true,
            ],
            [
                'category' => 'Vouchers',
                'question' => 'How do vouchers apply to bookings?',
                'description' => 'Ordinary booking-voucher redemption is a legacy/incomplete area and is not the same as promotions or gift-voucher sales.',
                'where' => '<a href="/admin/vouchers">Admin > Vouchers</a>',
                'business_rules' => 'Do not promise that an ordinary voucher code will reduce a current public or admin booking total. The historical voucher records remain for compatibility, but the legacy redemption/payment controls are hidden until a complete allocation, partial-use, balance, expiry, cancellation, and reporting model is agreed. Use Promotions for an approved booking discount. Use a Prepaid Voucher Partner wholesaler rate when the guest already paid an external partner. Gift vouchers are purchased through their separate date-free flow.',
                'related' => 'promotions, gift vouchers, prepaid voucher partners, wholesaler rates, legacy vouchers',
                'is_internal' => true,
            ],
            [
                'category' => 'Gift Vouchers',
                'question' => 'How are gift vouchers redeemed?',
                'description' => 'Gift vouchers are purchased through a date-free public checkout and remain separate from dated bookings.',
                'where' => '<a href="/front/welcome">Front > Welcome</a> > Buy as a Gift',
                'business_rules' => 'The buyer selects an Online Bookable service, the current applicable season and quantities, then pays through PayFast. Payment marks the gift voucher paid but not redeemed. Post-payment fulfilment generates the voucher document and sends the guest email when the site email settings allow it; fulfilment failure must not reverse the recorded payment and can be retried. Buying a voucher does not select a date or reserve capacity. A complete public/admin redemption and balance-allocation workflow is not currently confirmed, so staff must not describe purchase as automatic redemption during booking payment.',
                'related' => 'gift voucher, PayFast, voucher code, paid, redeemed, fulfilment',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'Can we book children only?',
                'description' => 'Yes - you can book children only (adults can be 0).',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'At least 1 adult OR 1 child is required. Selected slot is required to save. Capacity is enforced against remaining slots; Override Capacity allows exceeding capacity only when the service allows it. Override Time Slots opens a date-override modal to add internal-only slots for the selected booking date.',
                'examples' => 'If a slot has 4 remaining and you add 3 adults + 2 children, the form warns you unless Override Capacity is checked (and allowed). If you need an extra 12:00 slot on a specific day, toggle Override Time Slots and add it as an internal-only date override.',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'Why did my total change after saving?',
                'description' => 'Totals are recalculated from per-pax rates and rounded to 2 decimals on save.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Booking totals are derived from per-pax rates (adult/child/guide). Rates and per-pax totals are rounded to 2 decimals, then the booking total is rounded to 2 decimals. This prevents drift between the calculator, saved booking, and invoice. When a promo or discount is applied, changing pax re-runs pricing to keep totals consistent; promo input is hidden whenever a discount (line item or invoice) is active.',
                'examples' => 'If a promo gives a rate like 722.50, the saved total should remain consistent (e.g., 4 pax x 722.50 = 2,890.00). If you previously saw 2,905 -> 2,904 after save, resaving should now keep the same total.',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'What is Override Capacity vs Override Time Slots?',
                'description' => 'They control different things: capacity vs availability.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Override Capacity allows exceeding the service capacity for a booking (only if the service allows it). Override Time Slots lets you add a date-specific slot (internal-only by default) without changing the weekly schedule.',
                'examples' => 'Override Capacity: allow 9 pax on a slot with capacity 8 for an exception. Override Time Slots: add a 12:00 slot for one day even if the weekly schedule is only 09:00 + 13:00.',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'How do multi-activity bookings work?',
                'description' => 'A multi-activity booking lets staff add more than one activity under one lead customer and one booking reference.',
                'where' => '<a href="/admin/bookings/create">Admin > Bookings > New Booking</a>',
                'business_rules' => 'Each activity is saved as its own booking item with its own service, season, date, time slot, pax, and pricing. The parent booking keeps the shared lead/customer details, payment context, and invoice context. Promo, discount, wholesaler, and override-rate choices are scoped per activity and must not bleed into other activities. Payment remains at the parent booking level, and the invoice total is the sum of all active activity items.',
                'examples' => 'Example: one reference can contain Canopy Tour on Friday 09:30 for 3 pax and The Fear Factor on Saturday 10:00 for 2 pax. They show as separate operational rows but share one booking reference and invoice.',
                'related' => 'multi booking, linked activities, booking items, invoice, payment, booking index',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Create',
                'question' => 'Why do linked activities show as separate rows on the Bookings list?',
                'description' => 'The Bookings list is operationally date/time/slot based, so each activity appears where it will actually happen.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Multi-activity bookings keep one shared parent booking/reference/payment/invoice, but each activity is managed as a separate booking item on the booking index. A Linked badge appears when the reference has multiple activities. Clicking the badge shows the related activity records, even when they are on different dates. View/Edit actions pass the selected booking_item_id so staff manage the specific activity row, not always the first item.',
                'examples' => 'If REF-ABC123 has activities on July 17 and July 20, each date shows its own row. Click Linked 2 from either row to jump to the other activity.',
                'related' => 'multi booking, linked activities, booking index, edit booking, booking item',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Edit',
                'question' => 'Why does editing reissue invoices?',
                'description' => 'Edit bookings and reissue documents.',
                'where' => 'Open a booking from <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Edits reissue invoice and boarding pass. Expired seasons are included for the current booking so legacy bookings can be edited. The edit calendar considers the current booking pax for capacity checks, and Override Capacity can be used only when the service allows it.',
                'examples' => 'If a booking already holds 4 seats and the slot capacity is 8, you can increase it up to 8 on edit. To exceed 8, Override Capacity must be enabled (and allowed).',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Edit',
                'question' => 'What happens to discounts or promos when I move a booking to another date?',
                'description' => 'The edit screen tries to keep the current pricing mode when the booking date changes.',
                'where' => 'Open a booking from <a href="/admin/bookings">Admin > Bookings</a> and change the activity date/time.',
                'business_rules' => 'When a booking already has a promo, discount, wholesaler rate, or approved override price, changing the date should not silently reset the booking to rack rate. The system recalculates using the same pricing mode for the new service/date/season where it is still valid. If the promo is outside its valid booking/activity dates, or a wholesaler rate no longer exists for the selected season, staff must choose another valid price option or recalculate from rack before saving.',
                'examples' => 'If a birthday promo booking moves to another valid activity date, the promo remains applied. If the new date falls outside the promo activity period, the system warns you instead of saving an incorrect rack-rate total.',
                'related' => 'booking edit, promotions, discounts, wholesaler rates, override rates, recalculation',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Edit',
                'question' => 'How do I search for a guest or booking reference?',
                'description' => 'Use the search box on Admin > Bookings to filter the bookings list.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a> (Search box)',
                'business_rules' => 'Search starts once you type 3+ characters. It filters the bookings list (not a popup) and auto-expands matching rows so you can see the booking details. Matches include reference, lead name/email/phone, service, date, time, and the indemnity-complete guest (if different from lead). Search ignores the selected day/week/range, but the selected service filter still applies. Cancelled and payment-expired bookings are included only when Show cancelled / expired bookings is enabled.',
                'examples' => 'Search "REF-123", "amanda", "gmail.com", or a phone number. If a known booking is missing, check the service filter and Show cancelled / expired bookings.',
                'related' => 'bookings, search, guest history, reference, lead, email, phone',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'How do we record a manual payment?',
                'description' => 'Record offline payments or send an online PayLink to a guest.',
                'where' => 'Open a booking > Payments or <a href="/admin/booking-payments/create/BOOKING_ID">/admin/booking-payments/create/BOOKING_ID</a>',
                'business_rules' => 'Booking totals are VAT-inclusive. Offline payments (cash/EFT/card machine) can be recorded in admin. Booking Payment Amount is the portion allocated to the booking balance and cannot exceed the outstanding booking balance. The system reverse-calculates ex-VAT and VAT from that booking amount and stores the payment row separately. Additional Amount is optional for tips, food, drinks, or merchandise and is not added to the booking invoice VAT/balance. Partial payments accumulate against the booking until the balance is fully paid. Pay Online sends a signed PayLink to the booking lead email. Guest invoice/payment links do not expire; if a booking is already paid, the payment page shows that no online payment amount is outstanding. Some bookings can be marked paid by allocation without an actual payment row, especially prepaid wholesaler voucher partner bookings; in that case Payment History shows an allocation/context row instead of a payment transaction. The payment page is payment-only; use Edit Booking Price / Promo before payment when a promo, discount, birthday special, wholesaler rate, or rack-rate override must change the booking total. Do not treat the PayFast return page as confirmation; the PayFast notify callback is the source of truth for marking a payment COMPLETE.',
                'related' => 'payments, offline payments, cash, partial payment, additional amount, tip, food, merchandise, invoice, VAT, PayLink, promo, discount, booking edit',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'Where do we apply promos, birthday specials, discounts, or rate changes from payment?',
                'description' => 'Price changes are made on Booking Edit before a payment is recorded.',
                'where' => 'Open a booking > Payments > Edit Booking Price / Promo',
                'business_rules' => 'The admin payment screen only records money received. It does not apply promo codes, discounts, birthday specials, voucher-like discounts, wholesaler rates, or rack-rate overrides. Click Edit Booking Price / Promo to open Booking Edit, apply the price change, save/recalculate the booking and invoice, then return to the payment page to record the payment. If the booking has multiple activities, the payment page first shows an activity chooser so staff edit the correct booking item.',
                'examples' => 'For a birthday adult special, click Edit Booking Price / Promo, choose the relevant activity if asked, apply the BDAY promo on Booking Edit, save, then record the payment.',
                'related' => 'payments, booking edit, promo, birthday special, discounts, voucher, multi-activity, booking item, invoice',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'What happens if I record a partial or adjusted payment?',
                'description' => 'Partial booking payments and additional non-booking amounts are supported, but need a reason and generate an updated invoice PDF.',
                'where' => 'Open a booking > Payments or <a href="/admin/booking-payments/create/BOOKING_ID">/admin/booking-payments/create/BOOKING_ID</a>',
                'business_rules' => 'If Booking Payment Amount differs from the outstanding booking balance, a Short / Adjusted Payment Note is required. If Additional Amount is greater than zero, an Additional Amount Note is required. Booking paid total is updated cumulatively and remains capped at the booking invoice total. Additional Amount is stored on the payment record for extras such as tips, food, drinks, or merchandise; it does not affect booking VAT, invoice total, amount paid, or balance due. The payment page shows the latest previous short/additional notes in a read-only context panel, but does not preload previous notes or previous Additional Amount into the new payment inputs to avoid duplicate context/extras. After the payment is saved, the standard invoice PDF is regenerated with the updated Amount Paid and Balance Due, but it is not automatically emailed.',
                'examples' => 'If the outstanding balance is R2,505 and the guest pays R925 cash, enter R925 as Booking Payment Amount and add "Deposit paid in cash" in Short / Adjusted Payment Note. If the guest pays R2,505 plus a R100 tip, enter R2,505 as Booking Payment Amount, R100 as Additional Amount, and add "R100 guide tip" in Additional Amount Note.',
                'related' => 'partial payment, adjusted payment, additional amount, short payment note, additional amount note, tip, food, merchandise, invoice, resend invoice, amount paid, balance due',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'How do I send a PayFast payment link (PayLink)?',
                'description' => 'Send a secure signed link so the guest can pay online.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a> (Tools > Payments) or <a href="/admin/booking-payments/create/BOOKING_ID">/admin/booking-payments/create/BOOKING_ID</a>',
                'business_rules' => 'PayLink emails a signed checkout URL to the booking lead customer email. Guest payment links do not expire. If the booking is already paid, the payment page opens but does not offer PayFast checkout. PayFast notify/ITN remains the source of truth for marking the booking paid; the PayFast return page is only a customer-facing confirmation/processing page. If the lead email is missing on the booking, update the lead details on Booking Edit and try again.',
                'examples' => 'If the guest says they did not receive the link, confirm the lead email address and resend.',
                'related' => 'payfast, paylink, payments, checkout, signed link, booking',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'Why does PayFast sandbox send the receipt to a PayFast test email?',
                'description' => 'PayFast sandbox uses test accounts and may not use the guest email you entered.',
                'where' => 'PayFast sandbox checkout screen',
                'business_rules' => 'In sandbox mode, PayFast may display a receipt email like sbtu01@payfast.co.za. This is normal for sandbox testing and does not mean the booking email is wrong. For payment status, rely on the PayFast notify callback (COMPLETE) and the booking paid totals in admin.',
                'examples' => 'If you see "receipt sent to sbtu01@payfast.co.za" in sandbox, focus on whether notify returned COMPLETE and the booking shows paid.',
                'related' => 'payfast, sandbox, receipt email, notify, payments',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Cancellations',
                'question' => 'How do we cancel a slot?',
                'description' => 'Cancel a booking or a full slot.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a> (Tools menu)',
                'business_rules' => 'Cancel Slot soft-cancels every active booking item for the selected team, service, date, and time. If a parent booking has no active items left, the parent booking is also soft-cancelled. Records are retained for audit and can be shown with cancelled bookings. Reinstate restores the original booking/items only after capacity is checked; an authorised force-capacity override is a deliberate exception.',
                'related' => 'cancel booking, cancel slot, cancelled bookings, reinstate booking, soft delete, capacity',
                'is_internal' => false,
            ],
            [
                'category' => 'Bookings - Cancellations',
                'question' => 'How do we reinstate a cancelled booking?',
                'description' => 'Cancelled bookings can be shown and restored from the bookings index.',
                'where' => '<a href="/admin/bookings">Admin > Bookings</a> > Show cancelled / expired bookings',
                'business_rules' => 'Cancelled and unpaid payment-expired bookings are hidden by default. Turn on Show cancelled / expired bookings to review preserved inactive records. Cancelled bookings use the existing capacity-checked reinstatement flow. Payment-expired bookings use Restore Expired Booking, which blocks past activities and rechecks the original slot capacity without an over-capacity override. A restored expired booking becomes staff-managed, records the restoring user in the audit history, and will not expire automatically again.',
                'examples' => 'If an expired booking needs 2 places but only 1 remains, restoration is blocked. If capacity remains, restoring it returns the booking to the normal bookings view and records the staff user who restored it.',
                'related' => 'cancelled bookings, reinstate booking, show cancelled bookings, capacity override, soft delete, booking index',
                'is_internal' => false,
            ],
            [
                'category' => 'Invoices',
                'question' => 'Why are totals VAT inclusive?',
                'description' => 'Invoices are generated per booking.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Totals are VAT-inclusive; VAT is shown as a breakdown line without changing the total. Subtotal (Excl VAT) = Total / (1 + VAT rate). VAT Amount = Total - Subtotal. Amount Paid is also VAT-inclusive and must not be multiplied by the VAT rate again. Additional Amounts on admin payments are not added to the booking invoice total or VAT breakdown. When an admin payment is recorded, the stored invoice PDF is regenerated so Customer Invoice and Resend Invoice use the latest Amount Paid and Balance Due. Invoice notes use the site invoice note, not per-booking notes.',
                'related' => 'invoice, VAT, amount paid, balance due, payments, additional amount, resend invoice',
                'is_internal' => false,
            ],
            [
                'category' => 'Invoices',
                'question' => 'Why do prepaid voucher partner invoices show R0 but reports show a value?',
                'description' => 'Prepaid voucher partner bookings separate customer-facing invoice value from internal/reporting value.',
                'where' => 'Customer Invoice PDFs and <a href="/admin/reports">Admin > Reports</a> > Franchise Fees',
                'business_rules' => 'For wholesaler rates marked Prepaid Voucher Partner, the stored booking/fee amount remains the internal/reporting value used by admin totals and Franchise Fees. The customer invoice suppresses that partner settlement value: prepaid voucher lines show as paid externally and invoice subtotal/VAT/total/balance only include non-prepaid lines. This prevents the guest from seeing or being asked to pay the partner settlement amount while preserving reporting accuracy. Do not add a second amount column unless a future accounting requirement needs a separate settlement ledger.',
                'examples' => 'If Hyperli sells a voucher and the site receives/reporting value is R600, the booking keeps R600 for Franchise Fees. The customer invoice line says Paid externally and the invoice total due is R0 when there are no other payable activities.',
                'related' => 'invoice, prepaid voucher partner, wholesaler rates, Franchise Fees, paid externally, VAT',
                'is_internal' => true,
            ],
            [
                'category' => 'Invoices',
                'question' => 'How do developers safely regenerate existing invoice PDFs?',
                'description' => 'Use the invoice regeneration command when stored invoice PDFs need to be re-rendered after display/template logic changes.',
                'where' => '<code>php artisan invoices:regenerate</code> (server / CLI)',
                'business_rules' => 'Stored invoice PDFs are snapshots. If invoice display rules change, older PDFs keep the old layout/values until regenerated. Use <code>invoices:regenerate</code> for document re-rendering only; it does not recalculate booking prices, payments, or invoice database totals. The command is safe by default: without <code>--force</code> it runs as dry-run and writes a CSV report to <code>storage/app/reports</code>. Use narrow filters such as <code>--booking=</code>, <code>--team=</code>, <code>--from=</code>, <code>--to=</code>, <code>--type=std</code>, and <code>--prepaid-voucher-only</code>. Run dry-run first, inspect the CSV, then re-run with <code>--force</code>.',
                'examples' => '<p><strong>Preview one booking:</strong> <code>php artisan invoices:regenerate --booking=123 --dry-run</code></p><p><strong>Regenerate prepaid voucher invoices for a team/date window:</strong> <code>php artisan invoices:regenerate --team=3 --from=2026-07-01 --to=2026-07-31 --prepaid-voucher-only --force</code></p>',
                'related' => 'invoice regeneration, PDF snapshots, prepaid voucher partner, maintenance command, dry-run, reports CSV',
                'is_internal' => true,
            ],
            [
                'category' => 'Boarding Passes',
                'question' => 'Why is a boarding pass missing?',
                'description' => 'Boarding passes are generated from templates.',
                'where' => 'Templates: <a href="/admin/boardingpass">Admin > Boarding Pass Templates</a>. Preview from booking tools.',
                'business_rules' => 'Template selection prefers team+service+season; fallback to team default.',
                'is_internal' => false,
            ],
            [
                'category' => 'Manifests',
                'question' => 'Where does the manifest list come from?',
                'description' => 'Manifests list guests for a booking.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Manifest rows are built from booking pax. If customer data is missing, the system shows placeholder rows. On Booking Add, the default view focuses on Checked-in guests (indemnity complete); toggle All to see placeholders and guests who have not completed check-in yet.',
                'is_internal' => false,
            ],
            [
                'category' => 'Manifests',
                'question' => 'How do I show all guests vs checked-in only?',
                'description' => 'Use the manifest toggle to switch between All guests and Checked-in guests.',
                'where' => 'Booking Add > Customer Manifest (toggle in the header).',
                'business_rules' => 'Checked-in means the guest has completed indemnity (waiver + signature) and `customer_manifests.is_indemnity = 1`. The manifest defaults to Checked-in so staff can focus on completed forms. Switch to All to view and update guests who still need to complete indemnity.',
                'examples' => 'If you only see 1 guest but the booking has 4 pax, switch to All to view the other placeholders.',
                'related' => 'manifest, checked-in, indemnity, waiver, booking add',
                'is_internal' => false,
            ],
            [
                'category' => 'Manifests',
                'question' => 'What does Indemnity mean on the manifest?',
                'description' => 'Indemnity reflects whether the guest completed the waiver.',
                'where' => 'Booking Add > Customer Manifest',
                'business_rules' => 'The Indemnity badge maps to `customer_manifests.is_indemnity`. It is set when the guest completes the check-in/waiver (signature). It is not the same as Arrived.',
                'examples' => 'A guest can complete Indemnity online but still be marked as No Show/Cancelled in Arrived.',
                'related' => 'check-in, questionnaire, waiver, signature, manifest, booking add',
                'is_internal' => false,
            ],
            [
                'category' => 'Check-in',
                'question' => 'How do we send a check-in link?',
                'description' => 'Check-in links are signed and shared with guests.',
                'where' => 'Booking tools in <a href="/admin/bookings">Admin > Bookings</a>',
                'business_rules' => 'Signed links required; questionnaires auto-seed if missing. Check-in links expire at activity start time + 3 hours (booking date + time slot). If the signature expires but the activity window is still valid, the link is reissued automatically on open. The top Check-in button on Admin > Bookings is disabled if there are no bookings for the selected date or if the selected date is in the past.',
                'examples' => 'If you pick a day with no bookings, the Check-in button is disabled. If a guest opens an old link before the 3-hour window ends, the system reissues a signed link automatically.',
                'related' => 'indemnity, questionnaire, manifest, signed link, booking add, feedback',
                'is_internal' => false,
            ],
            [
                'category' => 'Check-in',
                'question' => 'How can staff run check-in on a tablet?',
                'description' => 'Use the staff check-in kiosk to open secure check-in forms without sharing admin access with guests.',
                'where' => '<a href="/admin/checkins/kiosk">Admin > Check-in Kiosk</a> or the Check in button on Admin > Bookings',
                'business_rules' => 'The kiosk is staff-login only. It lists bookings for the selected activity date and lets staff search by reference, guest, phone, email, or service. Start Check-in opens the same signed guest check-in form used by WhatsApp/email links, with a return-to-kiosk marker. After the guest submits, the tablet returns to the kiosk date list so staff can continue with the next guest.',
                'examples' => 'On the day of the tour, Rose logs into the tablet, opens Check-in Kiosk, taps Check In Guest, hands over the tablet, then gets returned to the kiosk after submit. This avoids a public reference-only lookup.',
                'related' => 'kiosk, tablet, indemnity, questionnaire, manifest, signed link, booking add',
                'is_internal' => false,
            ],
            [
                'category' => 'Check-in',
                'question' => 'Why does a check-in link say "Link Expired" or "Activity start time is not set"?',
                'description' => 'Signed links can expire, and some legacy bookings may not have a start time set.',
                'where' => '<a href="/front/booking-questionnaire/signed">Front > Check-in (signed link)</a>',
                'business_rules' => 'If the activity start time is missing on the booking inventory/time slot, the system cannot calculate the allowed expiry window and will show the expired screen. For valid bookings, links are allowed until activity start time + 3 hours. If a link is expired but still within the allowed window, the system can reissue a signed URL automatically.',
                'examples' => 'If a booking was created without a time slot, fix the booking time slot in admin and resend the check-in link.',
                'related' => 'check-in, signed link, expiry, time slot, booking inventory',
                'is_internal' => false,
            ],
            [
                'category' => 'Check-in',
                'question' => 'How do we mark Arrived / No Show / Cancelled?',
                'description' => 'Arrival status is tracked per guest on the manifest.',
                'where' => 'Booking Add > Customer Manifest (Arrived toggle) and Checkin Detail modal.',
                'business_rules' => 'Arrival status is stored on `customer_manifests.attendance_status` with values `no_show`, `arrived`, `cancelled` (default `no_show`). On the Booking Add list, Arrived toggles in a cycle (No -> Yes -> Cancelled -> No). In Checkin Detail, you can select the status with 3 buttons and also update notes/lunch option, then Save.',
                'examples' => 'If a guest checks in but does not arrive, set Arrived to No. If they arrived, set Yes. If the guest cancels, set Cancelled.',
                'related' => 'manifest, check-in, indemnity, attendance, booking add',
                'is_internal' => false,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'How do we manage check-in and feedback questions per service?',
                'description' => 'Questionnaires are managed via the dynamic question list (option 1).',
                'where' => '<a href="/admin/dynamic-questionnaires">Admin > Questionnaires</a>',
                'business_rules' => 'Questions live in `questions` and are enabled per service + season via `question_site` (enabled + sort order). There is no default fallback: each season has its own question_sites and those rows are the source of truth. The front check-in and feedback flows read this list directly, so toggles here immediately affect what guests see. Use Type = indemnity for check-in and Type = feedback for feedback. Services without seasons are hidden from the list.',
                'examples' => 'If a service should ask for meal preference, enable the `lunch_option` question for that service and set its sort order. Leave it disabled for services that do not offer meals.',
                'related' => 'indemnity, check-in, feedback, question_site, service, season, dynamic questionnaires',
                'is_internal' => true,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'How do lunch options work on check-in?',
                'description' => 'Lunch options are a custom question per service/season.',
                'where' => '<a href="/admin/dynamic-questionnaires">Admin > Questionnaires</a> (Season settings) and Booking Add > Checkin Detail',
                'business_rules' => 'Enable `lunch_option` for the service + season and provide custom options (CSV) in the season settings. When enabled, guests must pick a lunch option during check-in. Admin Checkin Detail shows a dropdown sourced from the same options and saves the selected value to `question_answers`.',
                'examples' => 'If a season offers Vegetarian/Fish/Chicken, enter those options and enable `lunch_option`. Guests cannot proceed without choosing one.',
                'related' => 'lunch_option, meal, questionnaire, check-in, booking add, question_site',
                'is_internal' => true,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'What do Visible, Default On, and Compulsory mean?',
                'description' => 'These flags live on the question library and control how seasons are seeded.',
                'where' => '<a href="/admin/dynamic-questionnaires">Admin > Questionnaires</a> (Question Library)',
                'business_rules' => 'Visible controls whether a question appears in the service toggle list. Default On sets the starting enabled state when a new season is created. Compulsory (is_disabled) locks a question on; it is always enabled and cannot be turned off in service settings.',
                'examples' => 'If Marketing Source should be optional, keep Compulsory off and Default On off. If Signature must always be present, mark it Compulsory.',
                'related' => 'question library, question_site, defaults, compulsory, is_visible, is_disabled',
                'is_internal' => true,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'Why is a service missing from the Questionnaire list?',
                'description' => 'Only services with seasons are listed.',
                'where' => '<a href="/admin/dynamic-questionnaires">Admin > Questionnaires</a>',
                'business_rules' => 'The dynamic list hides services with no seasons. Create a season for the service first, then manage its questions per season.',
                'related' => 'services, seasons, question_site, questionnaires',
                'is_internal' => true,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'How do I align existing seasons with the library defaults?',
                'description' => 'Use the sync command to reset enabled flags across all seasons.',
                'where' => 'CLI: php artisan question-sites:sync',
                'business_rules' => 'The sync updates question_sites.enabled to match question library defaults: enabled = Compulsory ? true : (Visible ? Default On : false). This is useful after changing library defaults.',
                'related' => 'question_site, question library, seasons, defaults, questionnaires',
                'is_internal' => true,
            ],
            [
                'category' => 'Questionnaires',
                'question' => 'Why do age fields not store literal ages?',
                'description' => 'Questionnaire templates and responses.',
                'where' => '<a href="/admin/dynamic-questionnaires">Admin > Questionnaires</a>',
                'business_rules' => 'Age values store age group IDs, not literal ages.',
                'is_internal' => true,
            ],
            [
                'category' => 'Feedback',
                'question' => 'When are feedback forms available?',
                'description' => 'Guest feedback forms.',
                'where' => '<a href="/front/feedback-questionnaire">Front > Feedback Questionnaire</a>',
                'business_rules' => 'Links are signed and available after activity dates. Feedback links expire 7 days after the activity completes (booking date + time slot + service duration; end-of-day fallback if no start time). The top Feedback button on Admin > Bookings is disabled if there are no bookings for the selected date, if the date is in the future, or if no check-ins have been completed for that date.',
                'examples' => 'If a booking is for next week, Feedback is disabled until the activity date passes. If there are bookings today but no guests have completed check-in, Feedback remains disabled. If a guest opens the link after the 7-day window, they see the expired screen with the team contact email.',
                'related' => 'feedback, check-in, indemnity, questionnaires, signed links',
                'is_internal' => true,
            ],
            [
                'category' => 'Check-in',
                'question' => 'Why does the check-in screen say "complete" or not complete?',
                'description' => 'Completion is per booking item, not per individual.',
                'where' => '<a href="/front/booking-questionnaire/checkin">Front > Check-in</a>',
                'business_rules' => 'The completion message only appears once all pax (adults + children) for the booking item have submitted indemnity. If only some guests are done, the link stays open so the remaining guests can complete their forms.',
                'examples' => 'If a booking has 4 pax and only 1 has completed, the screen stays open and does not mark complete. Share the same link with the remaining guests.',
                'is_internal' => true,
            ],
            [
                'category' => 'Messaging',
                'question' => 'Where are manual guest messages logged?',
                'description' => 'Guest email history and internal staff conversations are separate records.',
                'where' => 'Booking Add > Email History for guest email records; <a href="/admin/messenger">Admin > Messages</a> for staff-to-staff conversations',
                'business_rules' => 'Operational guest sends that create an email audit row are shown against the booking in Email History. Admin > Messages is the internal staff messenger and its unread count appears in the header; it is not the customer email audit. A copied WhatsApp link opens WhatsApp Web outside the application and is not proof that the guest received or read the message.',
                'related' => 'email history, guest email, internal messages, WhatsApp, audit',
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
                'category' => 'Alerts',
                'question' => 'How do check-in notifications work?',
                'description' => 'Check-in notifications warn staff about questionnaire and manifest items that need review.',
                'where' => 'Header notification bell and <a href="/admin">Admin > Dashboard</a> Notifications table.',
                'business_rules' => 'Check-in alerts are issue-only. Completing an indemnity does not create a notification by itself. Alerts are created for declarations or problems that need staff review, such as medical conditions, pregnancy, not fit to walk, or terms not agreed. Guests may complete the indemnity more than once to correct information; any new issue declaration creates a fresh alert. Contextual alerts open a quick-view modal showing the guest, booking reference, service/date/time, manifest status, and questionnaire answers. Use <strong>Acknowledge</strong> after review. Acknowledging does not change guest answers or make a guest eligible to participate; it only records that staff reviewed the notification. If the alert is linked to a manifest, staff can append an acknowledgement note to Checkin Detail notes without overwriting existing notes.',
                'examples' => 'If the alert says Medical Condition or Pregnancy, open it from the bell or dashboard, review the answer, optionally add an acknowledgement note, then use View Booking if you need to update the manifest or contact the guest. If the guest did not agree to terms, staff must handle that operationally; acknowledgement does not override the indemnity answer.',
                'related' => 'alerts, notifications, check-in, indemnity, manifest, questionnaire, dashboard, Checkin Detail, acknowledge',
                'is_internal' => false,
            ],
            [
                'category' => 'Alerts',
                'question' => 'Why did a notification disappear after I acknowledged it?',
                'description' => 'Acknowledged alerts are hidden once one authorised staff member reviews them.',
                'where' => 'Header notification bell and <a href="/admin">Admin > Dashboard</a> Notifications table.',
                'business_rules' => 'Acknowledgement is team-level for the assigned team. When one authorised staff member clicks Acknowledge, the system marks the alert complete for all assigned staff on that team and removes it from their header and dashboard notification lists. This does not delete the alert for audit/history purposes, and it does not automatically complete or change any customer manifest answer. If you ticked the note option, the acknowledgement text is appended to the customer manifest notes so it remains visible in Checkin Detail.',
                'examples' => 'Rose acknowledging a Medical Condition alert hides it from the Karkloof team alert lists. The guest answer remains unchanged, and the appended Checkin Detail note remains available for staff review.',
                'related' => 'alerts, acknowledge, notification, dashboard, header bell, Checkin Detail notes, manifest',
                'is_internal' => false,
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
                'category' => 'Reports',
                'question' => 'Which reports are available right now?',
                'description' => 'Operational reports available to staff.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'Currently available reports are Bookings, Payments, Check-in/Manifest, Booking Adjustment, Marketing Opt-In, Franchise Fees, Wholesaler Usage, and Promo Code Usage. All reports are team-scoped and support download, queued email delivery, or online viewing in a new browser tab. Online viewing requires a start and end date and is limited to a maximum two-month period; use download or email for larger ranges.',
                'examples' => 'Use Bookings to export pax and totals by activity date. Use Payments to export payment transactions by payment date. Use Check-in/Manifest to export guest check-in status and optional manifest fields. Use Booking Adjustment to compare rack totals vs adjusted totals. Use Marketing Opt-In for guests who opted into birthday marketing. Use Franchise Fees for the monthly franchise worksheet. Use Wholesaler Usage to review operator/accommodation partner bookings by date range. Use Promo Code Usage to review promo-code bookings, pax, value, and discount impact.',
                'is_internal' => true,
            ],
            [
                'category' => 'Reports',
                'question' => 'What does the Promo Code Usage report show?',
                'description' => 'Promotion and promo-code booking activity.',
                'where' => '<a href="/admin/reports">Admin > Reports</a> > Promo Code Usage',
                'business_rules' => 'Promo Code Usage is based on activity date and only includes active bookings with promotion pricing. It can be filtered by date range, promo code, and service. The export starts with a summary by promo and then lists booking-level detail including promo name/code/type, apply mode, pax, rack total, promo adjustment, net total, amount paid, payment status, agent, and booking note. Cancelled bookings and soft-deleted booking items are excluded by default.',
                'examples' => 'Use this to see how many bookings and pax used a birthday promo or seasonal promo for a month, what the rack value would have been, what the net value was, and what discount impact was applied.',
                'related' => 'reports, promotions, promo code, birthday special, booking pricing, discounts',
                'is_internal' => false,
            ],
            [
                'category' => 'Reports',
                'question' => 'What does the Wholesaler Usage report show?',
                'description' => 'Wholesaler, accommodation, operator, and partner booking activity.',
                'where' => '<a href="/admin/reports">Admin > Reports</a> > Wholesaler Usage',
                'business_rules' => 'Wholesaler Usage is based on activity date and only includes active bookings with wholesaler pricing. It can be filtered by date range, wholesaler, and service. The export starts with a summary by wholesaler and then lists booking-level detail including pax, rack total, wholesaler total, adjustment, amount paid, payment status, trade-exchange flag, prepaid-voucher flag, bill-back flag, agent, and booking note. Cancelled bookings and soft-deleted booking items are excluded by default.',
                'examples' => 'Use this to see how many bookings and pax came from a specific accommodation provider or operator for a month, what the rack value would have been, what the wholesaler value was, and whether the booking has been paid.',
                'related' => 'reports, wholesaler, accommodation, operator, trade exchange, prepaid voucher partner, booking pricing',
                'is_internal' => false,
            ],
            [
                'category' => 'Reports',
                'question' => 'How does the Franchise Fees report group rates?',
                'description' => 'Monthly franchise worksheet buckets for head office feedback.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'Franchise Fees is based on activity date and stored booking item totals. It groups known structured pricing into Full Rate, Complimentary, Trade Exchange, Voucher Partner, 10%, 15%, 20%, 25%, and Other. The report can include all services or a selected set of services. Leaving all service boxes unticked means all services; ticking services limits the report to only those services. Wholesaler rows marked Trade Exchange are treated as Trade Exchange. Wholesaler rates marked Prepaid Voucher Partner are classified as Voucher Partner before the Complimentary fallback, so a paid-up/zero-balance external voucher booking does not become Complimentary. Percent promos and discounts map to matching percentage buckets when possible. Non-percent promos, unmapped discounts, manual/custom rates, student-style rates without a structured flag, and override prices go to Other with structured pricing comments only. Operational booking notes and invoice notes are deliberately excluded from Franchise Fees comments.',
                'examples' => 'For a Pilot Rollout report, tick only Canopy Tour and 2 Line to exclude Hiking and Fear Factor. A 10% discount line goes into the 10% bucket. A prepaid voucher partner wholesaler rate goes into Voucher Partner Pax/Income. A fixed birthday promo, custom override, or informal student-style discount goes into Other unless a specific bucket is later agreed and captured as structured data. If a custom/manual rate has no structured override reason, the Special Comments value shows Custom rate - no report reason captured instead of using the booking note.',
                'related' => 'franchise fees, reports, promotions, discounts, wholesaler, booking adjustment, VAT',
                'is_internal' => true,
            ],
            [
                'category' => 'Reports',
                'question' => 'How do prepaid voucher partners show on Franchise Fees?',
                'description' => 'Prepaid voucher partner bookings are reported separately from complimentary/free bookings.',
                'where' => '<a href="/admin/reports">Admin > Reports</a> > Franchise Fees',
                'business_rules' => 'The report uses the saved wholesaler rate flag from the booking. If the applied wholesaler rate was marked Prepaid Voucher Partner, the booking is counted under Voucher Partner Pax/Income even when the customer balance is R0. This keeps external voucher/platform bookings separate from true complimentary bookings. Trade Exchange remains controlled by the wholesaler trade-exchange flag, and normal unflagged wholesaler/special rates fall into their configured or fallback bucket.',
                'examples' => 'If a guest already paid an external voucher platform and staff used a prepaid voucher partner wholesaler rate of R600, the Franchise Fees report shows that pax/income in Voucher Partner instead of Complimentary.',
                'related' => 'franchise fees, wholesaler rates, prepaid voucher, voucher partner, complimentary, trade exchange, reports',
                'is_internal' => false,
            ],
            [
                'category' => 'Reports',
                'question' => 'How are payment totals calculated in the Payments report?',
                'description' => 'Payment exports use stored payment fields.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'The export includes stored `ex_vat_amount`, `vat_amount`, `additional_amount`, `short_payment_note`, and `additional_payment_note` values. New admin/offline payments and front/simulated payments store ex-VAT and VAT portion correctly, so Booking Payment Amount = ex_vat_amount + vat_amount. Additional Amount is reported separately and Total Received = Booking Payment Amount + Additional Amount. Legacy admin payment rows may have an inflated `vat_amount` (ex_vat_amount * vat), so the report booking amount falls back to `ex_vat_amount` when `vat_amount` is larger than `ex_vat_amount`.',
                'examples' => 'Legacy row: if ex_vat_amount is 2505.00 and vat_amount is 941253.75, the report booking amount will use 2505.00. Current row: if ex_vat_amount is 804.35, vat_amount is 120.65, and additional_amount is 100.00, Booking Payment Amount = 925.00 and Total Received = 1025.00.',
                'related' => 'payments, VAT, reports, admin offline payments, additional amount, legacy payment rows',
                'is_internal' => true,
            ],
            [
                'category' => 'Reports',
                'question' => 'How do the Check-in/Manifest optional fields work?',
                'description' => 'Optional columns for group type, nationality, referral source, gender, and safety declarations.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'The optional columns pull from questionnaire answers by slug: `type_group_id` for Group Type, `country_id`/`nationality_id` for Nationality (fallback to customer nationality if missing), `referral_id` for Referral Captured, `gender`/`gender_id` for Gender, `medical_id` for Medical, `medical_detail` for Medical Details, `is_fit` for Fit To Walk, and `pregnant` for Pregnancy. If Pregnancy was not asked/stored for a completed indemnity, the report treats it as No. Other missing optional values are left blank. Checked-in is based on indemnity completion; Arrived is based on attendance status. The report excludes orphan legacy manifest rows that no longer have booking/date context. By default the report shows the latest manifest/check-in row per booking item and customer, and also collapses likely duplicate guest rows with the same booking item and same guest name. Tick Show Duplicate Check-ins to export repeated/duplicate check-in rows as well; duplicate mode adds Manifest Created At and Manifest Updated At columns so the latest record is clear.',
                'examples' => 'If a guest reports a medical condition, tick Medical and Medical Details on the Check-in/Manifest report to export the declaration by activity date. If a guest completed check-in twice to correct details, or was accidentally captured twice with the same name, the default report shows the latest row. Tick Show Duplicate Check-ins when auditing every submission.',
                'is_internal' => true,
            ],
            [
                'category' => 'Reports',
                'question' => 'How is Booking Status shown in reports?',
                'description' => 'Status display for bookings in exports.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'Operational reports exclude cancelled bookings and soft-deleted booking items by default. Booking Status remains available for active rows, but cancelled rows should not appear in standard report exports. If cancellation reporting is needed later, build it as a separate explicit cancellation report/filter.',
                'examples' => 'If a booking is cancelled via admin tools, it is excluded from standard Bookings, Payments, Check-in/Manifest, Booking Adjustment, Marketing Opt-In, and Franchise Fees exports.',
                'is_internal' => true,
            ],
            [
                'category' => 'Reports',
                'question' => 'How is Booking Adjustment calculated?',
                'description' => 'Rack vs adjusted totals in the Booking Adjustment report.',
                'where' => '<a href="/admin/reports">Admin > Reports</a>',
                'business_rules' => 'Rack Total uses season rake rates at export time (rake_rate_adult/child/guide) multiplied by booked pax. Net Total uses booking fee totals. Adjustment = Rack Total - Net Total. If historical rack rates change later, adjustment totals can shift unless rack totals are stored at booking time.',
                'examples' => 'If a season rate is edited after the booking, the report will reflect the new rack rate. To preserve historical accuracy, store rack totals per booking item.',
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
                'business_rules' => 'Enable Pickup/Dropoff shows or hides pickup/dropoff fields. Enable Guest Email Sending is the master per-site email switch. Manual Email Sending controls deliberate staff sends such as Message Guest, Send Payment Link, Resend Invoice, and check-in/payment links. Auto Send Invoice/Quote controls booking create/update auto-sends. Auto Send Payment Confirmation controls frontend payment completion emails. Show Guest Indemnity Email Option controls whether guests see an optional checkbox to receive their signed indemnity PDF by email; the guest checkbox stays off by default. Show Send Invoice/Quote Checkbox controls UI visibility only; backend sends must still pass the email settings.',
                'examples' => 'If Enable Guest Email Sending is OFF, no guest emails should send. If it is ON but Manual Email Sending is OFF, staff can still view/copy links but deliberate email sends are blocked. If Auto Send Invoice is OFF and Manual Email Sending is ON, staff can send manually only where the UI exposes a send action.',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'How are booking settings stored and applied?',
                'description' => 'Settings are stored as lookup items per team.',
                'where' => 'Admin > Settings and booking create/edit/add',
                'business_rules' => 'Settings use `lookup_items.value` (boolean) under the `settings` lookup. Booking Create/Edit reads the settings map via `LookupItem::settingsForTeam()` for UI toggles. Email business rules are centralized in `App\Services\EmailDispatchService`; env mail config only controls transport/sandboxing and must not decide per-site auto/manual sends. Use `php artisan settings:backfill` to create missing rows (note: backfill resets values to off).',
                'examples' => 'If a team has no settings rows, backfill will create them and default all toggles to Off, then staff can enable only what they need.',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'Why do unpaid online bookings expire?',
                'description' => 'Booking V2 has a short checkout hold followed by a longer provisional-booking payment window.',
                'where' => 'Public V2 checkout, PayFast payment result, and scheduled command <code>bookings:expire-unpaid-front</code>',
                'business_rules' => 'Before Confirm & Pay, Continue creates a lightweight session-owned capacity hold when holds are enabled. It normally lasts 15 minutes and does not create a customer, booking, invoice, or payment record. Confirm & Pay revalidates the hold, capacity, price, promo, extras, and terms in one transaction, then creates the pending provisional booking. The longer payment window is configured per site in hours and runs from the latest PayFast submit or booking creation when PayFast was not opened. A value of 0 means unpaid frontend bookings never expire automatically. The scheduled expiry command marks overdue unpaid frontend bookings as payment-window expired and releases capacity without permanently deleting their booking, customer, invoice, or gateway history. Admin bookings, Pay Later bookings, and staff-restored expired bookings are not affected.',
                'examples' => 'Letting the checkout countdown expire returns the guest to availability without creating a booking. Cancelling PayFast leaves the provisional booking pending until payment succeeds or the longer payment window expires.',
                'related' => 'checkout hold, frontend payment, PayFast, provisional booking, capacity release, payment expiry',
                'is_internal' => true,
            ],
            [
                'category' => 'Bookings - Payments',
                'question' => 'What happens when the V2 checkout is refreshed, cancelled, times out, or is paid late?',
                'description' => 'The server, not the browser countdown, owns hold and payment expiry.',
                'where' => 'Public V2 checkout and PayFast result pages',
                'business_rules' => 'Refreshing checkout resumes the same server expiry time and does not restart the short hold. If the hold expires before Confirm & Pay, the guest returns to availability and must choose an available slot again. A cancelled or incomplete PayFast attempt does not mark the booking paid; the provisional booking remains pending until payment succeeds or the scheduled expiry releases its capacity. A valid PayFast notify received after provisional expiry is never discarded: payment is recorded, the booking is restored to operational visibility, and staff receive a capacity-review alert because the released space may have been sold again. PayFast notify/ITN is the payment source of truth, not the browser return page.',
                'related' => 'Booking V2, refresh, timeout, PayFast cancel, late payment, capacity alert, notify',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'What does GA4 purchase tracking include?',
                'description' => 'The booking platform sends a purchase event only for verified paid PayFast bookings and gift vouchers.',
                'where' => 'Public PayFast return page, Google Tag Manager, and GA4',
                'business_rules' => 'A standard <code>purchase</code> event is prepared only after stored payment state is paid. Included transactions are paid public bookings, staff-created bookings paid through the tracked signed PayLink, and paid public gift vouchers. Cancelled, declined, unpaid, simulated, or staff-recorded offline payments do not emit a purchase. Booking value is the VAT-inclusive core activity amount after promotions and deliberately excludes optional extras, tips, and later additional payments. Gift value is the stored core voucher total. The event contains transaction/product/site information but no guest name, email, or telephone number. Stable transaction IDs and browser suppression reduce duplicate events after refresh.',
                'examples' => 'A R2,200 PayFast payment containing a R1,790 activity plus R410 optional extras reports purchase value R1,790. A cancelled PayFast result reports no purchase.',
                'related' => 'GA4, Google Tag Manager, ecommerce, PayFast, purchase, gift voucher, extras',
                'is_internal' => true,
            ],
            [
                'category' => 'System / Settings',
                'question' => 'How are PayFast gateway credentials stored?',
                'description' => 'Payment gateway credentials are encrypted with a stable gateway config key.',
                'where' => 'Admin > Site and server env configuration',
                'business_rules' => 'PayFast/team gateway credentials are stored in `payment_gateway_configs.config` and encrypted through the `GatewayConfig` model. Use `PAYMENT_GATEWAY_CONFIG_KEY` for stable credential encryption instead of relying only on Laravel `APP_KEY`. This prevents a future `php artisan key:generate` or database restore/key mismatch from crashing Site settings or making credentials unreadable. The model first tries the stable key, then legacy APP_KEY decryption, then encrypted string JSON fallback; if none work it logs a warning and returns an empty config so the Site page can still load.',
                'examples' => 'After adding or changing `PAYMENT_GATEWAY_CONFIG_KEY`, deploy, run `php artisan optimize:clear`, then `php artisan gateway-configs:reencrypt --dry-run`. If rows are skipped, those credentials cannot be recovered with the available keys and must be re-entered.',
                'related' => 'payfast, gateway credentials, PAYMENT_GATEWAY_CONFIG_KEY, encryption, APP_KEY, site settings, gateway-configs:reencrypt',
                'is_internal' => true,
            ],
        ];
    }

    private function legacyQuestionVariant(string $question): string
    {
        // Historically, some help questions were saved with curly quotes due to copy/paste
        // from rich text. When we normalize text, we still want to update the existing
        // DB row (instead of creating a duplicate).

        $out = '';
        $doubleIndex = 0;

        $len = strlen($question);
        for ($i = 0; $i < $len; $i++) {
            $ch = $question[$i];

            if ($ch === '"') {
                // Alternate open/close: “ ... ”
                $out .= ($doubleIndex % 2 === 0) ? "\u{201C}" : "\u{201D}";
                $doubleIndex++;

                continue;
            }

            if ($ch === "'") {
                // Right single quote for apostrophes: don’t, promo’s, etc.
                $out .= "\u{2019}";

                continue;
            }

            $out .= $ch;
        }

        return $out;
    }

    protected function formatAnswer(
        string $description,
        string $where = '',
        string $businessRules = '',
        string $examples = '',
        string $related = ''
    ): string {
        $description = trim($description);
        $where = trim($where);
        $businessRules = trim($businessRules);
        $examples = trim($examples);
        $related = trim($related);

        $parts = [];
        $parts[] = '<p><strong>Description:</strong> '.($description !== '' ? $description : 'N/A').'</p>';
        $parts[] = '<p><strong>Where:</strong> '.($where !== '' ? $where : 'N/A').'</p>';
        $parts[] = '<p><strong>Business Rules:</strong> '.($businessRules !== '' ? $businessRules : 'N/A').'</p>';
        if ($examples !== '') {
            $parts[] = '<p><strong>Example:</strong> '.$examples.'</p>';
        }
        if ($related !== '') {
            $parts[] = '<p><strong>Related:</strong> '.$related.'</p>';
        }

        return implode('', $parts);
    }
}
