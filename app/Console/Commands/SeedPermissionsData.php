<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInfo;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingVoucher;
use App\Models\BookingVoucherItem;
use App\Models\Closeout;
use App\Models\CloseoutDayTimeSlot;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\Discount;
use App\Models\DiscountService;
use App\Models\Inventory;
use App\Models\InventoryTimeSlot;
use App\Models\InventoryTypeDay;
use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\ServiceField;
use App\Models\ServiceFieldValue;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use App\Models\TypeDay;
use App\Models\TypePayOption;
use App\Models\TypePayment;
use App\Models\TypeBooking;
use App\Models\TypeAffiliate;
use App\Models\TypeCategory;
use App\Models\TypeData;
use App\Models\TypeFile;
use App\Models\TypeGroup;
use App\Models\TypeInput;
use App\Models\File;
use App\Models\Gateway;
use App\Models\InvoiceTemplate;
use App\Models\Lookup;
use App\Models\LookupItem;
use App\Models\Promotion;
use App\Models\Question;
use App\Models\Questionnaire;
use App\Models\QuestionnaireField;
use App\Models\QuestionSite;
use App\Models\StatusPayment;
use App\Models\TypeAlert;
use App\Models\TypeConfirmation;
use App\Models\TypeGender;
use App\Models\TypeHelpQuestion;
use App\Models\TypeInvoice;
use App\Models\TypeLocation;
use App\Models\TypeReferral;
use App\Models\VatRate;
use App\Models\Wholesaler;
use App\Models\WholesalerRate;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\Type;
use Str;

class SeedPermissionsData extends Command
{
    protected $signature = 'seed:permissions-data'; // php artisan seed:permissions-data
    protected $description = 'Create or update core seed data for permissions, roles, etc.';

    private function filterPermissions(array $denyNames, array $denyPrefixes)
    {
        $base = Permission::whereNotIn('name', $denyNames)->get();

        if (empty($denyPrefixes)) {
            return $base;
        }

        return $base->filter(function ($permission) use ($denyPrefixes) {
            foreach ($denyPrefixes as $prefix) {
                if (str_starts_with($permission->name, $prefix)) {
                    return false;
                }
            }
            return true;
        });
    }

    public function handle()
    {
        $this->info('Seeding core data...');

        $tableKey = 'Tables_in_' . DB::getDatabaseName();

        $tables = collect(DB::select('SHOW TABLES'))
            ->pluck($tableKey)
            ->reject(fn($table) => str_starts_with($table, 'type_') || str_starts_with($table, 'status_'))
            ->values();

        $crudActions = ['create', 'edit', 'show', 'delete', 'access'];

        $permissions = [
            ['name' => 'user_management_access'],
            ['name' => 'dashboard_access'],
            ['name' => 'bookings_access'],
            ['name' => 'messenger_access'],
            ['name' => 'reports_access'],
            ['name' => 'help_questions_access'],
            ['name' => 'services_access'],
            ['name' => 'markets_access'],
            ['name' => 'wholesalers_access'],
            ['name' => 'sites_access'],
            ['name' => 'boarding_pass_templates_access'],
        ];

        foreach ($tables as $table) {
            foreach ($crudActions as $action) {
                $permissions[] = [
                    'name' => $table . '_' . $action,
                ];
            }
        }

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }
        $this->info('permissions seeded successfully.' . count($permissions));
        $roles = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Director'],
            ['id' => 3, 'name' => 'Manager'],
            ['id' => 4, 'name' => 'Consultant'],
            ['id' => 5, 'name' => 'Lead Guide'],
            ['id' => 6, 'name' => 'Safety Guide'],
        ];
        foreach ($roles as $role) {
            Role::updateOrCreate(['id' => $role['id']], $role);
        }
        $this->info('Roles seeded successfully.');

        // Assign permissions
        $adminPermissions = Permission::all();
        Role::findOrFail(1)->permissions()->sync($adminPermissions->pluck('id'));

        $directorPermissions = $this->filterPermissions(
            [
                'audit_logs_access',
                'booking_questionnaires_access',
                'teams_create'
            ],
            ['users_', 'roles_', 'permissions_', 'permission_role']
        );
        Role::findOrFail(2)->permissions()->sync($directorPermissions->pluck('id'));

        $managerPermissions = $this->filterPermissions(
            [
                'audit_logs_access',
                'booking_questionnaires_access',
                'invoice_templates_access',
                'help_questions_access',
                'user_alerts_access',
                'vouchers_access',
                'teams_create',
                'boarding_pass_templates_access'
            ],
            ['roles_', 'permissions_', 'permission_role']
        );
        Role::findOrFail(3)->permissions()->sync($managerPermissions->pluck('id'));

        $userPermissions = $this->filterPermissions(
            [
                'audit_logs_access',
                'booking_questionnaires_access',
                'invoice_templates_access',
                'help_questions_access',
                'user_alerts_access',
                'vouchers_access',
                'teams_create',
                'boarding_pass_templates_access'
            ],
            ['users_', 'roles_', 'permissions_', 'permission_role']
        );
        Role::findOrFail(4)->permissions()->sync($userPermissions->pluck('id'));
        Role::findOrFail(5)->permissions()->sync($userPermissions->pluck('id'));

        // one time seeder for teams and users
        if (false) {
            // Cape - Ryan and Mark 1 Mark mark@canopytour.co.za Ryan Larkman <ryan@capecanopytour.co.za>, 
            // Tsitsikamma - Lee Marque 7 Lee-Marque Jansen <marketing@stormsriver.com>,  Anneline <anneline@stormsriver.com>, Ashley Wentworth <ash@stormsriver.com>, 
            // Karkloof - Rose (info@karkloofcanopytour.co.za) 3 Info <info@karkloofcanopytour.co.za>, 
            // Magoebaskloof - Jane 5 Jane Allison <jane@thabametsi.com>, 
            // Magaliesberg - Conrad 4 Conrad <conrad@khondloexpeditions.co.za>,
            // Drakensberg - Kai 2 Kai Schulz <kai@canopytour.co.za>, gm@drakensbergcanopytour.co.za, 

            // sandile.mngomezulu@entc.org.sz (Eswatini / regional tourism body)
            // marcnone7@gmail.com (personal Gmail, unclear role)
            // oupa@foodworx.net (supplier/service)
            // ash@stormsriver.com (Stormsriver staff, but not one of the “top names” listed)
            // jason@whitehart.co.za (external / unrelated) 


            // Teams
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('teams')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Team::updateOrCreate(['id' => 1], [
                'name' => 'Cape',
                'team_id' => 1,
                // 'description' => 'The Tsitsikamma team is a group of volunteers who are dedicated to helping the community of Tsitsikamma. We are passionate about making a positive impact on the lives of the people who live here and are committed to making a difference.',
                // 'image' => '/images/teams/tsitsikamma.jpg',
            ]);
            Team::updateOrCreate(['id' => 2], ['name' => 'Drakensberg', 'team_id' => 2,]);
            Team::updateOrCreate(['id' => 3], ['name' => 'Karkloof', 'team_id' => 3,]);
            Team::updateOrCreate(['id' => 4], ['name' => 'Magaliesberg', 'team_id' => 4,]);
            Team::updateOrCreate(['id' => 5], ['name' => 'Magoebaskloof', 'team_id' => 5,]);
            // 
            Team::updateOrCreate(['id' => 6], [
                'name' => 'Tsitsikamma',
                'full_name' => 'Tsitsikamma Canopy Tours',
                'description' => '<h3>Storms River Adventures </h3> <p>Come and Experience a World of Diversity in One Forest </p> <p>A Unique Tsitsikamma Experience You’ll Never Forget!.</p>',
                // 'logo' => '/images/teams/tsitsikamma.jpg',
                // 'image' => '/images/teams/tsitsikamma.jpg',
                'color' => '#31B700',
                'reg_no' => '0000/0000/00',
                'address' => 'PO Box 0000, Somewhere',
                'address_code' => '0000',
                'account_owner' => 'Tsitsikamma Canopy Tours',
                'account_number' => '1234567890',
                'account_type'   => 'Cheque',
                'bank_name'   => 'FNB',
                'branch_code' => '50001',
                'team_id' => 6,
            ]);
            Team::updateOrCreate(['id' => 7], [
                'name' => 'Test Team',
                'team_id' => 7,
                // 'full_name' => 'Tsitsikamma',
                // 'description' => '<h3 style="color:white;">Stormriver Adventures </h3> <p style="color:white;">Come and Experience a World of Diversity in One Forest </p> <p style="color:white;">A Unique Tsitsikamma Experience You’ll Never Forget!.</p>',
                // 'logo' => '/images/teams/tsitsikamma.jpg',
                // 'image' => '/images/teams/tsitsikamma.jpg',
                // 'color' => '#31B700',
                // 'reg_no' => '2008/018998/07',
                // 'address' => 'PO Box 3132, Tzaneen',
                // 'address_code' => '0850',
                // 'owner' => 'Magoebaskloof Canopy Tours',
                // 'number' => '1234567890',
                // 'type'   => 'Cheque',
                // 'name'   => 'ABSA',
                // 'branch_code' => '632005',
            ]);
            // Users
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('users')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $users = [
                [
                    'id'             => 1,
                    'name'           => 'Jason',
                    'surname'        => 'Admin',
                    'email'          => 'jason@inspireonline.co.za',
                    'password'       => bcrypt('jason@inspireonline1'),
                    'team_id'        => 7,
                    'is_admin'       => true,
                    'is_email_recipient' => false,
                ],
                [ // Cape
                    'id'             => 2,
                    'name'           => 'Mark',
                    'surname'        => '',
                    'email'          => 'mark@canopytour.co.za',
                    'password'       => bcrypt('mark@canopytour1'),
                    'team_id'        => 1,
                    'is_admin'       => false,
                    'is_email_recipient' => false,
                ],
                [ // Cape
                    'id'             => 3,
                    'name'           => 'Ryan',
                    'surname'        => 'Larkman',
                    'email'          => 'ryan@capecanopytour.co.za',
                    'password'       => bcrypt('ryan@capecanopytour'),
                    'team_id'        => 1,
                    'is_admin'       => 1,
                    'is_email_recipient' => false,
                ],
                [ // Drakensberg
                    'id'             => 4,
                    'name'           => 'Kai',
                    'surname'        => 'Schulz',
                    'email'          => 'kai@canopytour.co.za',
                    'password'       => bcrypt('kai@canopytour1'),
                    'team_id'        => 2,
                    'is_admin'       => false,
                    'is_email_recipient' => false,
                ],
                [ // Karkloof
                    'id'             => 5,
                    'name'           => 'Rose-Lynn',
                    'surname'        => 'Paine',
                    'email'          => 'info@karkloofcanopytour.co.za', // Rose-Lynn Paine Admin Reception Manager, Karkloof Canopy Tour 076 241 2888
                    'password'       => bcrypt('info@karkloofcanopytour1'),
                    'team_id'        => 3,
                    'is_admin'       => false,
                    'is_email_recipient' => false,
                ],
                [ // magaliesberg
                    'id'             => 6,
                    'name'           => 'Conrad',
                    'surname'        => '',
                    'email'          => 'conrad@khondloexpeditions.co.za',
                    'password'       => bcrypt('conrad@khondloexpeditions1'),
                    'team_id'        => 4,
                    'is_admin'       => false,
                    'is_email_recipient' => false,
                ],
                [ // Magoebaskloof
                    'id'             => 7,
                    'name'           => 'Jane',
                    'surname'        => 'Allison',
                    'email'          => 'jane@thabametsi.com',
                    'password'       => bcrypt('jane@thabametsi1'),
                    'team_id'        => 5,
                    'is_admin'       => false,
                    'is_email_recipient' => false,
                ],
                [ // tsitsikamma
                    'id'             => 8,
                    'name'           => 'Lee-Marque',
                    'surname'        => 'Jansen',
                    'email'          => 'marketing@stormsriver.com',
                    'password'       => bcrypt('marketing@stormsriver1'),
                    'team_id'        => 6,
                    'is_admin'       => false,
                    'is_email_recipient' => false,
                ],
            ];
            foreach ($users as $user) {
                User::updateOrCreate(['id' => $user['id']], $user);
            }
        }

        User::findOrFail(1)->roles()->sync([1]); //admin
        User::findOrFail(2)->roles()->sync([2]); //director
        User::findOrFail(3)->roles()->sync([3]); //manager
        User::findOrFail(4)->roles()->sync([3]); //manager
        User::findOrFail(5)->roles()->sync([3]); //manager
        User::findOrFail(6)->roles()->sync([3]); //manager
        User::findOrFail(7)->roles()->sync([3]); //manager
        User::findOrFail(8)->roles()->sync([3]); //manager

        // php artisan seed:permissions-data
    }
}
