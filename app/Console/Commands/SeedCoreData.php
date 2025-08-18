<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInventory;
use App\Models\BookingPayment;
use App\Models\Closeout;
use App\Models\CloseoutDayTimeSlot;
use App\Models\Customer;
use App\Models\CustomerManifest;
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
use App\Models\StatusBooking;
use App\Models\TypeAffiliate;
use App\Models\TypeCategory;
use App\Models\TypeData;
use App\Models\TypeFile;
use App\Models\TypeGroup;
use App\Models\TypeInput;
use App\Models\File;
use App\Models\Gateway;
use App\Models\StatusPayment;
use App\Models\TypeConfirmation;
use App\Models\TypeGender;
use App\Models\TypeLocation;
use App\Models\TypeReferral;
use App\Models\Wholesaler;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\Type;
use Str;

class SeedCoreData extends Command
{
    protected $signature = 'seed:core-data'; // php artisan seed:core-data
    protected $description = 'Create or update core seed data for permissions, roles, etc.';

    public function handle()
    {
        $this->info('Seeding core data...');

        // $permissions = [
        //     [
        //         'id'    => 1,
        //         'name' => 'user_management_access',
        //     ],
        //     [
        //         'id'    => 2,
        //         'name' => 'permission_create',
        //     ],
        //     [
        //         'id'    => 3,
        //         'name' => 'permission_edit',
        //     ],
        //     [
        //         'id'    => 4,
        //         'name' => 'permission_show',
        //     ],
        //     [
        //         'id'    => 5,
        //         'name' => 'permission_delete',
        //     ],
        //     [
        //         'id'    => 6,
        //         'name' => 'permission_access',
        //     ],
        //     [
        //         'id'    => 7,
        //         'name' => 'role_create',
        //     ],
        //     [
        //         'id'    => 8,
        //         'name' => 'role_edit',
        //     ],
        //     [
        //         'id'    => 9,
        //         'name' => 'role_show',
        //     ],
        //     [
        //         'id'    => 10,
        //         'name' => 'role_delete',
        //     ],
        //     [
        //         'id'    => 11,
        //         'name' => 'role_access',
        //     ],
        //     [
        //         'id'    => 12,
        //         'name' => 'user_create',
        //     ],
        //     [
        //         'id'    => 13,
        //         'name' => 'user_edit',
        //     ],
        //     [
        //         'id'    => 14,
        //         'name' => 'user_show',
        //     ],
        //     [
        //         'id'    => 15,
        //         'name' => 'user_delete',
        //     ],
        //     [
        //         'id'    => 16,
        //         'name' => 'user_access',
        //     ],
        //     [
        //         'id'    => 17,
        //         'name' => 'audit_log_show',
        //     ],
        //     [
        //         'id'    => 18,
        //         'name' => 'audit_log_access',
        //     ],
        //     [
        //         'id'    => 19,
        //         'name' => 'team_create',
        //     ],
        //     [
        //         'id'    => 20,
        //         'name' => 'team_edit',
        //     ],
        //     [
        //         'id'    => 21,
        //         'name' => 'team_show',
        //     ],
        //     [
        //         'id'    => 22,
        //         'name' => 'team_delete',
        //     ],
        //     [
        //         'id'    => 23,
        //         'name' => 'team_access',
        //     ],
        //     [
        //         'id'    => 24,
        //         'name' => 'service_access',
        //     ],
        //     [
        //         'id'    => 25,
        //         'name' => 'service_create',
        //     ],
        //     [
        //         'id'    => 26,
        //         'name' => 'service_edit',
        //     ],
        //     [
        //         'id'    => 27,
        //         'name' => 'service_show',
        //     ],
        //     [
        //         'id'    => 28,
        //         'name' => 'service_delete',
        //     ],
        //     [
        //         'id'    => 29,
        //         'name' => 'season_access',
        //     ],
        //     [
        //         'id'    => 30,
        //         'name' => 'season_create',
        //     ],
        //     [
        //         'id'    => 31,
        //         'name' => 'season_edit',
        //     ],
        //     [
        //         'id'    => 32,
        //         'name' => 'season_show',
        //     ],
        //     [
        //         'id'    => 33,
        //         'name' => 'season_delete',
        //     ],
        //     [
        //         'id'    => 34,
        //         'name' => 'inventory_access',
        //     ],
        //     [
        //         'id'    => 35,
        //         'name' => 'inventory_create',
        //     ],
        //     [
        //         'id'    => 36,
        //         'name' => 'inventory_edit',
        //     ],
        //     [
        //         'id'    => 37,
        //         'name' => 'inventory_show',
        //     ],
        //     [
        //         'id'    => 38,
        //         'name' => 'inventory_delete',
        //     ],
        //     [
        //         'id'    => 39,
        //         'name' => 'task_access',
        //     ],
        //     [
        //         'id'    => 40,
        //         'name' => 'tasks_calendar_access',
        //     ],
        //     [
        //         'id'    => 41,
        //         'name' => 'profile_password_edit',
        //     ],
        //     [
        //         'id'    => 42,
        //         'name' => 'booking_access',
        //     ],
        //     [
        //         'id'    => 43,
        //         'name' => 'booking_create',
        //     ],
        //     [
        //         'id'    => 44,
        //         'name' => 'booking_edit',
        //     ],
        //     [
        //         'id'    => 45,
        //         'name' => 'booking_show',
        //     ],
        //     [
        //         'id'    => 46,
        //         'name' => 'booking_delete',
        //     ],
        // ];
        // foreach ($permissions as $permission) {
        //     Permission::updateOrCreate(['id' => $permission['id']], $permission);
        // }

        $tableKey = 'Tables_in_' . DB::getDatabaseName();

        $tables = collect(DB::select('SHOW TABLES'))
            ->pluck($tableKey)
            ->reject(fn($table) => str_starts_with($table, 'type_') || str_starts_with($table, 'status_'))
            ->values();

        $crudActions = ['create', 'edit', 'show', 'delete', 'access'];

        $permissions = [
            ['name' => 'user_management_access'],
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
        Role::findOrFail(2)->permissions()->sync($adminPermissions->pluck('id'));
        Role::findOrFail(3)->permissions()->sync($adminPermissions->pluck('id'));

        $userPermissions = $adminPermissions->filter(
            fn($p) =>
            !str_starts_with($p->name, 'user_') &&
                !str_starts_with($p->name, 'role_') &&
                !str_starts_with($p->name, 'permission_')
        );
        Role::findOrFail(4)->permissions()->sync($userPermissions->pluck('id'));
        Role::findOrFail(5)->permissions()->sync($userPermissions->pluck('id'));

        // Teams
        Team::updateOrCreate(['id' => 1], ['name' => 'Cape']);
        Team::updateOrCreate(['id' => 2], ['name' => 'Drakensburg']);
        Team::updateOrCreate(['id' => 3], ['name' => 'Karkloof']);
        Team::updateOrCreate(['id' => 4], ['name' => 'Magaliesberg']);
        Team::updateOrCreate(['id' => 5], ['name' => 'Magoebskloof']);
        Team::updateOrCreate(['id' => 6], ['name' => 'Malolotja']);
        Team::updateOrCreate(['id' => 7], ['name' => 'Tsitsikamma']);

        // Users
        $users = [
            [
                'id'             => 1,
                'name'           => 'Jason',
                'surname'        => 'Admin',
                'email'          => 'jason@admin.com',
                'password'       => bcrypt('password'),
                'team_id'        => null,
                'is_admin'       => true,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 2,
                'name'           => 'Mark',
                'surname'        => 'Director',
                'email'          => 'mark@director.com',
                'password'       => bcrypt('password'),
                'team_id'        => null,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 3,
                'name'           => 'Manager',
                'surname'        => 'Tsitsikamma',
                'email'          => 'manager@tsitsikamma.com',
                'password'       => bcrypt('password'),
                'team_id'        => 7,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 4,
                'name'           => 'Manager',
                'surname'        => 'Magaliesberg',
                'email'          => 'manager@magaliesberg.com',
                'password'       => bcrypt('password'),
                'team_id'        => 4,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 5,
                'name'           => 'Consultant',
                'surname'        => 'Magaliesberg',
                'email'          => 'consultant@magaliesberg.com',
                'password'       => bcrypt('password'),
                'team_id'        => 4,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 6,
                'name'           => 'Guide1',
                'surname'        => 'Magaliesberg',
                'email'          => 'guide1@magaliesberg.com',
                'password'       => bcrypt('password'),
                'team_id'        => 4,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 7,
                'name'           => 'Guide2',
                'surname'        => 'Magaliesberg',
                'email'          => 'guide2@magaliesberg.com',
                'password'       => bcrypt('password'),
                'team_id'        => 4,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 8,
                'name'           => 'Consultant',
                'surname'        => 'tsitsikamma',
                'email'          => 'consultant@tsitsikamma.com',
                'password'       => bcrypt('password'),
                'team_id'        => 7,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 9,
                'name'           => 'Guide1',
                'surname'        => 'tsitsikamma',
                'email'          => 'guide1@tsitsikamma.com',
                'password'       => bcrypt('password'),
                'team_id'        => 7,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 10,
                'name'           => 'Guide2',
                'surname'        => 'tsitsikamma',
                'email'          => 'guide2@tsitsikamma.com',
                'password'       => bcrypt('password'),
                'team_id'        => 7,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 11,
                'name'           => 'Guide3',
                'surname'        => 'tsitsikamma',
                'email'          => 'guide3@tsitsikamma.com',
                'password'       => bcrypt('password'),
                'team_id'        => 7,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
            [
                'id'             => 12,
                'name'           => 'Guide4',
                'surname'        => 'tsitsikamma',
                'email'          => 'guide4@tsitsikamma.com',
                'password'       => bcrypt('password'),
                'team_id'        => 7,
                'is_admin'       => false,
                'is_email_recipient' => false,
            ],
        ];
        foreach ($users as $user) {
            User::updateOrCreate(['id' => $user['id']], $user);
        }

        User::findOrFail(1)->roles()->sync([1]); //admin
        User::findOrFail(2)->roles()->sync([2]); //director
        User::findOrFail(3)->roles()->sync([3]); //manager
        User::findOrFail(4)->roles()->sync([3]); //manager
        User::findOrFail(5)->roles()->sync([4]); //consultant
        User::findOrFail(6)->roles()->sync([5]); //guide
        User::findOrFail(7)->roles()->sync([5]); //guide
        User::findOrFail(8)->roles()->sync([4]); //consultant
        User::findOrFail(9)->roles()->sync([5]); //guide
        User::findOrFail(10)->roles()->sync([5]); //guide
        User::findOrFail(11)->roles()->sync([6]); //guide
        User::findOrFail(12)->roles()->sync([6]); //guide
        // TypeDays
        $typeDays = [
            ['id' => 1, 'name' => 'm'],
            ['id' => 2, 'name' => 't'],
            ['id' => 3, 'name' => 'w'],
            ['id' => 4, 'name' => 't'],
            ['id' => 5, 'name' => 'f'],
            ['id' => 6, 'name' => 's'],
            ['id' => 7, 'name' => 's'],
        ];
        foreach ($typeDays as $day) {
            TypeDay::updateOrCreate(['id' => $day['id']], $day);
        }

        // TypePayOptions
        $payOptions = [
            ['id' => 1, 'name' => 'Pay Offline'],
            ['id' => 2, 'name' => 'Pay OnLine'],
            ['id' => 3, 'name' => 'Split Payments'],
        ];
        foreach ($payOptions as $opt) {
            TypePayOption::updateOrCreate(['id' => $opt['id']], $opt);
        }

        // StatusPayments
        $statusPayments = [
            ['id' => 1, 'name' => 'pending'],
            ['id' => 2, 'name' => 'paid'],
            ['id' => 3, 'name' => 'failed'],
            ['id' => 4, 'name' => 'cancelled'],
        ];
        foreach ($statusPayments as $opt) {
            StatusPayment::updateOrCreate(['id' => $opt['id']], $opt);
        }

        // Gateways
        $gateways = [
            ['id' => 1, 'name' => 'PayFast'],
            ['id' => 2, 'name' => 'Manual'],
            ['id' => 3, 'name' => 'Stripe'],
        ];
        foreach ($gateways as $opt) {
            Gateway::updateOrCreate(['id' => $opt['id']], $opt);
        }

        // TypePayments
        $typePayments = [
            ['id' => 1, 'name' => 'Cash'],
            ['id' => 2, 'name' => 'Cheque'],
            ['id' => 3, 'name' => 'Credit Card'],
            ['id' => 4, 'name' => 'Debit Card'],
            ['id' => 5, 'name' => 'Direct Deposit / EFT'],
            ['id' => 6, 'name' => 'Masterpass'],
            ['id' => 7, 'name' => 'Paypal'],
            ['id' => 8, 'name' => 'Snapscan'],
            ['id' => 9, 'name' => 'Zapper'],
            ['id' => 10, 'name' => 'Voucher'],
        ];
        foreach ($typePayments as $payment) {
            TypePayment::updateOrCreate(['id' => $payment['id']], $payment);
        }

        // StatusBookings
        $statuses = [
            ['id' => 1, 'name' => 'quote'],
            ['id' => 2, 'name' => 'invoice'],
            ['id' => 3, 'name' => 'sent'],
            ['id' => 4, 'name' => 'paid'],
            ['id' => 5, 'name' => 'unpaid'],
            ['id' => 6, 'name' => 'cancelled'],
        ];
        foreach ($statuses as $status) {
            StatusBooking::updateOrCreate(['id' => $status['id']], $status);
        }

        $groupTypes = [
            ['id' => 1, 'name' => 'Individual'],
            ['id' => 2, 'name' => 'Couple'],
            ['id' => 3, 'name' => 'Family'],
            ['id' => 4, 'name' => 'Friends/Group'],
            ['id' => 5, 'name' => 'Corporate'],
            ['id' => 6, 'name' => 'Organised Group (school, hiking, etc)'],
        ];
        foreach ($groupTypes as $groupType) {
            TypeGroup::updateOrCreate(['id' => $groupType['id']], $groupType);
        }

        $affiliateTypes = [
            ['id' => 1, 'name' => 'Repeat Guest'],
            ['id' => 2, 'name' => 'Brochure'],
            ['id' => 3, 'name' => 'Internet'],
            ['id' => 4, 'name' => 'Travel Guide'],
            ['id' => 5, 'name' => 'Travel Agent'],
            ['id' => 6, 'name' => 'TV or Newspaper'],
        ];
        foreach ($affiliateTypes as $affiliateType) {
            TypeReferral::updateOrCreate(['id' => $affiliateType['id']], $affiliateType);
        }

        $type_categories = [
            ['id' => 1, 'name' => 'Details'],
            ['id' => 2, 'name' => 'Financial Details'],
            ['id' => 3, 'name' => 'Locations'],
            ['id' => 4, 'name' => 'Notifications'],
            ['id' => 5, 'name' => 'Shared Resources'],
        ];
        foreach ($type_categories as $type_category) {
            TypeCategory::updateOrCreate(['id' => $type_category['id']], $type_category);
        }
        // <ul class="dropdown-menu">
        // <li><a href="/services/11271/edit_details">Details</a></li>
        // <li><a href="/services/11271/edit_transport">Transport &amp; Locations</a></li>

        // <li><a href="/services/11271/edit_resources">Resources</a></li>
        // <li><a href="/services/11271/edit_financial">Financial Details</a></li>
        // <li><a href="/services/11271/edit_distribution">Distribution</a></li>
        // <li><a href="/services/11271/edit_seasons">Seasons</a></li>
        // <li><a href="/services/11271/edit_rates">Rates</a></li>
        // <li><a href="/services/11271/edit_inventories">Inventories</a>
        // </li><li><a href="/services/11271/edit_tickets">Tickets &amp; Manifest</a>
        // </li><li><a href="/services/11271/edit_check_in">Check-In</a></li>
        // <li><a href="/services/11271/confirmation_templates">Confirmation Templates</a></li>
        // <li><a href="/services/11271/option_groups">Service Options</a></li>
        // <li><a href="/services/11271/additional_informations/edit">Additional Information</a></li>
        // <li><a href="/services/11271/portal_featured/edit">Featured Services</a></li>
        // <li><a href="/services/11271/edit_integrated_distributors">Integrated Distributors</a></li></ul>


        $type_datas = [
            ['id' => 1, 'name' => 'text'],
            ['id' => 2, 'name' => 'long text'],
            ['id' => 3, 'name' => 'integer'],
            ['id' => 4, 'name' => 'float'],
            ['id' => 5, 'name' => 'date'],
            ['id' => 5, 'name' => 'time'],
            ['id' => 6, 'name' => 'boolean'],
        ];
        foreach ($type_datas as $type_data) {
            TypeData::updateOrCreate(['id' => $type_data['id']], $type_data);
        }

        $type_inputs = [
            ['id' => 1, 'name' => 'text'],       // <input type="text">
            ['id' => 2, 'name' => 'textarea'],   // <textarea>
            ['id' => 3, 'name' => 'number'],     // <input type="number">
            ['id' => 4, 'name' => 'date'],       // <input type="date">
            ['id' => 5, 'name' => 'time'],       // <input type="time">
            ['id' => 6, 'name' => 'checkbox'],   // <input type="checkbox">
        ];
        foreach ($type_inputs as $type_input) {
            TypeInput::updateOrCreate(['id' => $type_input['id']], $type_input);
        }

        $type_locations = [
            ['id' => 1, 'name' => 'pickup'],
            ['id' => 2, 'name' => 'start'],
            ['id' => 3, 'name' => 'dropoff'],
            ['id' => 4, 'name' => 'end'],
        ];
        foreach ($type_locations as $type_location) {
            TypeLocation::updateOrCreate(['id' => $type_location['id']], $type_location);
        }

        $type_confirmations = [
            ['id' => 1, 'name' => 'Yes'],
            ['id' => 2, 'name' => 'No'],
            ['id' => 3, 'name' => 'N/A'],
        ];
        foreach ($type_confirmations as $type_confirmation) {
            TypeConfirmation::updateOrCreate(['id' => $type_confirmation['id']], $type_confirmation);
        }

        $type_genders = [
            ['id' => 1, 'name' => 'Male'],
            ['id' => 2, 'name' => 'Female'],
            ['id' => 3, 'name' => 'N/A'],
        ];
        foreach ($type_genders as $type_gender) {
            TypeGender::updateOrCreate(['id' => $type_gender['id']], $type_gender);
        }

        $service_fields = [
            //['id' => 1, 'name' => 'header_pillbox_text','slug' => 'header_pillbox_text', 'type_category' => 1, 'order' => 1, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],
            ['id' => 1, 'name' => 'Description', 'slug' => 'description', 'type_category' => 1, 'order' => 1, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 2, 'name' => 'Condition Text', 'slug' => 'condition_text', 'type_category' => 1, 'order' => 2, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 3, 'name' => 'Activity Description', 'slug' => 'activity_description', 'type_category' => 1, 'order' => 3, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 4, 'name' => 'Arrangements', 'slug' => 'arrangements', 'type_category' => 1, 'order' => 4, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 5, 'name' => 'Requirements', 'slug' => 'requirements', 'type_category' => 1, 'order' => 5, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 6, 'name' => 'Inclusions', 'slug' => 'inclusions', 'type_category' => 1, 'order' => 6, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 7, 'name' => 'Exclusions', 'slug' => 'exclusions', 'type_category' => 1, 'order' => 7, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 8, 'name' => 'Notes', 'slug' => 'notes', 'type_category' => 1, 'order' => 8, 'type_data_id' => 2, 'type_input_id' => 2, 'required' => 1],
            ['id' => 9, 'name' => 'lat-lon start', 'slug' => 'lat_lon_start', 'type_category' => 3, 'order' => 9, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],
            ['id' => 10, 'name' => 'lat-long end', 'slug' => 'lat_long_end', 'type_category' => 3, 'order' => 10, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],
            //['id' => 12, 'name' => 'pillbox_condition_text','slug' => 'pillbox_condition_text', 'type_category' => 1, 'order' => 8, 'type_data_id' => 1, 'type_input_id' => 1, 'required' => 1],

        ];

        foreach ($service_fields as $service_field) {
            ServiceField::updateOrCreate(['id' => $service_field['id']], $service_field);
        }

        $type_files = [
            ['id' => 1, 'name' => 'logo'],
            ['id' => 2, 'name' => 'service'],
        ];
        foreach ($type_files as $type_file) {
            TypeFile::updateOrCreate(['id' => $type_file['id']], $type_file);
        }

        $wholesalers = [
            [
                'id' => 1,
                'name' => 'Wholesaler 1',
                'team_id' => 4,
            ],
            [
                'id' => 2,
                'name' => 'Wholesaler 2',
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'name' => 'Wholesaler 3',
                'team_id' => 7,
            ],
            [
                'id' => 4,
                'name' => 'Wholesaler 4',
                'team_id' => 7,
            ],
        ];
        foreach ($wholesalers as $wholesaler) {
            Wholesaler::updateOrCreate(['id' => $wholesaler['id']], $wholesaler);
        }

        $services = [
            [
                'id' => 1,
                'tag' => 'Canopy Tour',
                'name' => 'Tsitsikamma Canopy Tour',
                'duration' => '02:00:00',
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 2,
                'tag' => 'Woodcutters',
                'name' => 'Tsitsikamma Woodcutters Journey',
                'duration' => '02:00:00',
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 3,
                'tag' => 'Guided Hikes',
                'name' => 'Tsitsikamma Guided Hikes Green route: 5.09km',
                'duration' => '02:00:00',
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 4,
                'tag' => 'Guided Hikes',
                'name' => 'Tsitsikamma Guided Hikes Red route: 7.78km',
                'duration' => '02:00:00',
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 5,
                'tag' => 'Guided Hikes',
                'name' => 'Tsitsikamma Guided Hikes Yellow route 8.09km',
                'duration' => '02:00:00',
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
            [
                'id' => 6,
                'tag' => 'Team Building',
                'name' => 'Tsitsikamma Canopy Tour Photographic Scavenger Hunt',
                'duration' => '02:00:00',
                'allow_internal_capacity_override' => 1,
                'pillbox_conditions' => 'Book Now From 21 February 2025',
                'team_id' => 7,
            ],
        ];
        foreach ($services as $service) {
            Service::updateOrCreate(['id' => $service['id']], $service);
        }


        $description = "Come and Experience a World of Diversity in One Forest A Unique Tsitsikamma Experience You'll Never Forget!!!";
        $condition_text = "";
        $activity = "Experience a World of Diversity in One Forest – Just a short drive from Knysna and 2 hours from Port-Elizabeth. Situated in the Garden of the Garden Route and heart of Tsitsikamma – Stormsriver Village. Tsitsikamma Canopy Tour is an Award-Winning Canopy Tour company that turns Tourism activities into Tourism experiences! Enjoy being suspended 30m above the forest floor on the Tsitsikamma Canopy Tour, cable sliding from ten platforms, tree to tree while discovering the spectacular and unique wilderness of the Tsitsikamma Indigenous forest. Surrounded by birdlife and giant trees like the Outeniqua Yellowwood, known to us as Uncle Sam who is over 600 years old! One can easily feel like a dwarf standing against it. The tour begins with a detailed safety briefing followed by a ‘kitting up’ session where a full-body harness, pulleys, and climbing equipment is issued and checked. A short drive into the Tsitsikamma forest and it is time to monkey around! From the start on the launch platform, a new world unfolds as one gently glides on a steel cable to the first tree platform. All groups are escorted through the forest canopy by a lead guide and a follow guide to ensure your safety as you slide from tree to tree. The platforms built high up in the giant Outeniqua Yellowwoods, provide an unbelievable view of this enchanting new world – any fear of heights is soon lost as you gather in your new surroundings.";
        $arrangements = "Duration: Open Ended<br>When: All Year Round<br>Where: Stormsriver Village, Tsitsikamma<br>Weather: All-weather activity. Bring appropriate clothing.<br>Things to Bring: All additional items";
        $requirements = "Minimum Age: 5 years<br>Minimum Height: 0.0m<br>Fitness Level: Average<br>Certification: Not Applicable";
        $inclusions = "Inclusions: Bottled water, safety gear, guide, certificate<br>Exclusions: All additional items";
        $exclusions = "Exclusions All additional items.";
        $notes = "Things to remember Guests who are differently abled or have unique needs must please book via telephone or email to share their specific requirements so that we can cater to their needs and ensure you enjoy your experience to the fullest. Discount vouchers and over-the-counter or telephonic promotions are not applicable to online bookings after full payment has been captured on an online booking. Tours include bottled water, gear, and safety equipment as well as guides Clients are advised that the tour includes a 600m hike out afterward. Moms to be: We apologize – this tour is not for you while you are pregnant. We suggest a weight restriction per person of approximately 120kg to 130kg – this restriction is not weight-related but rather imposed by the size of the harnesses we use and is based on the circumference of the straps around the body. ●Our minimum age is 5 years old, and the children can do the tour in tandem with the guide. The activity is an all-weather activity. Even in the rain – just bring something warm to wear and come and enjoy a rainforest experience with us. It is in the Tsitsikamma Indigenous forest and is always a magical experience. A rain jacket will be supplied. Comfortable clothes should be worn and any footwear that is secure on your feet and that will not fall if you are suspended in mid-air. (Closed shoes) Long/Braided or Styled Hair: Please note that a helmet will have to fit onto your head. Hair cannot be tied on top of your head obstructing the helmet to comfortably protect your head.";
        $lat_lon_start = "0,0";
        $lat_long_end = "1,1";



        $serviceFieldValues = [
            ['service_id' => 1, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 1, 'field_id' => 2, 'value' => '1 Adult',],
            ['service_id' => 1, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 1, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 1, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 1, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 1, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 1, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 1, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 1, 'field_id' => 10, 'value' => $lat_long_end,],
            ['service_id' => 2, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 2, 'field_id' => 2, 'value' => 'Requires a minimum of 2 People',],
            ['service_id' => 2, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 2, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 2, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 2, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 2, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 2, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 2, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 2, 'field_id' => 10, 'value' => $lat_long_end,],
            ['service_id' => 3, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 3, 'field_id' => 2, 'value' => 'Book Now From 24 February 2025',],
            ['service_id' => 3, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 3, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 3, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 3, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 3, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 3, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 3, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 3, 'field_id' => 10, 'value' => $lat_long_end,],
            ['service_id' => 4, 'field_id' => 1, 'value' => $description,],
            ['service_id' => 4, 'field_id' => 2, 'value' => 'Requires a minimum of 2 People',],
            ['service_id' => 4, 'field_id' => 3, 'value' => $activity,],
            ['service_id' => 4, 'field_id' => 4, 'value' => $arrangements,],
            ['service_id' => 4, 'field_id' => 5, 'value' => $requirements,],
            ['service_id' => 4, 'field_id' => 6, 'value' => $inclusions,],
            ['service_id' => 4, 'field_id' => 7, 'value' => $exclusions,],
            ['service_id' => 4, 'field_id' => 8, 'value' => $notes,],
            ['service_id' => 4, 'field_id' => 9, 'value' => $lat_lon_start,],
            ['service_id' => 4, 'field_id' => 10, 'value' => $lat_long_end,],
        ];

        foreach ($serviceFieldValues as $serviceFieldValue) {
            ServiceFieldValue::updateOrCreate(
                [
                    'service_id' => $serviceFieldValue['service_id'],
                    'field_id' => $serviceFieldValue['field_id'],
                ],
                [
                    'value' => $serviceFieldValue['value'],
                    'team_id' => null,
                ]
            );
        }

        $files = [
            [
                "id" => 1,
                "table_name" => "services",
                "table_row_id" => "1",
                "file_name" => "card1.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/1/VG1575vJQfQGmo6TFAk5gE0DknbgZ00qYNv5XR2Q.jpg",
                "expiry" => null,
                "team_id" => null,
                "type_file_id" => 1
            ],
            [
                "id" => 2,
                "table_name" => "services",
                "table_row_id" => "6",
                "file_name" => "card1.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/6/A8f8mauVaWW4C6REWtltV3WXnrK1u7bRqNqvDMo0.jpg",
                "expiry" => null,
                "team_id" => null,
                "type_file_id" => 1
            ],
            [
                "id" => 3,
                "table_name" => "services",
                "table_row_id" => "3",
                "file_name" => "card3.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/3/gSjvgA1MyCqhhcxqdVOFNu4AAX9hAUdocjhZuVLV.jpg",
                "expiry" => null,
                "team_id" => null,
                "type_file_id" => 1
            ],
            [
                "id" => 4,
                "table_name" => "services",
                "table_row_id" => "4",
                "file_name" => "card4.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/4/ahRAMEjm1Udj3OGILUq9YnzSz7KNsSVl3KDRIxUc.jpg",
                "expiry" => null,
                "team_id" => null,
                "type_file_id" => 1
            ],
            [
                "id" => 5,
                "table_name" => "services",
                "table_row_id" => "5",
                "file_name" => "card5.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/5/P9BTOjmG1DiaLDMfxU7S7Tg3AewtJBitOzP1TTyd.jpg",
                "expiry" => null,
                "team_id" => null,
                "type_file_id" => 1
            ],
            [
                "id" => 6,
                "table_name" => "services",
                "table_row_id" => "2",
                "file_name" => "card6.jpg",
                "file_type" => "image/jpeg",
                "path" => "services/2/2y6BtQAwOEuAEt98H5p0Nuyab00mhWM4yqgJHJXW.jpg",
                "expiry" => null,
                "team_id" => null,
                "type_file_id" => 1
            ]
        ];

        foreach ($files as $file) {
            File::updateOrCreate(
                [
                    'team_id'      => null, // auth()->user()->team_id,
                    'table_name'   => 'services',
                    'table_row_id' => $file['table_row_id'],
                    'file_name'    => $file['file_name'],
                    'file_type'    => $file['file_type'],
                    'type_file_id' => 1, // optional enum/type
                    'path'         => $file['path'],
                    'expiry'       => $file['expiry'],
                ],
            );
        }
        $this->info('Files seeded successfully.');


        $this->info('Services seeded successfully.');
        $seasons = [
            [
                'id' => 1,
                'name' => 'february 2025',
                'start_date' => '2025-02-01',
                'end_date' => '2025-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'allow_internal_price_override' => 1,
                'capacity' => 8,

                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 1,
            ],
            [
                'id' => 2,
                'name' => 'year 2025',
                'start_date' => '2025-02-01',
                'end_date' => '2026-10-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'allow_internal_price_override' => 1,
                'capacity' => 4,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 2,
            ],
            [
                'id' => 3,
                'name' => 'year 2025',
                'start_date' => '2025-02-01',
                'end_date' => '2026-10-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'allow_internal_price_override' => 1,
                'capacity' => 4,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 3,
            ],
            [
                'id' => 4,
                'name' => 'autumn 2025',
                'start_date' => '2025-03-01',
                'end_date' => '2026-06-01',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'capacity' => 8,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 4,
            ],
            [
                'id' => 5,
                'name' => 'half year 2025',
                'start_date' => '2025-02-01',
                'end_date' => '2026-08-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'allow_internal_price_override' => 1,
                'capacity' => 5,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 5,
            ],
            [
                'id' => 6,
                'name' => 'year 2025',
                'start_date' => '2025-03-01',
                'end_date' => '2026-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'capacity' => 6,
                'allow_internal_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 6,
            ],

            [
                'id' => 7,
                'name' => 'year 2026',
                'start_date' => '2026-03-01',
                'end_date' => '2027-02-28',
                'rake_rate_adult' => '600.00',
                'rake_rate_child' => '300.00',
                'rake_rate_guide' => '200.00',
                'capacity' => 6,
                'allow_internal_override' => 1,
                'status' => 0,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'team_id' => 7,
                'service_id' => 6,
            ],
        ];




        foreach ($seasons as $season) {
            $season['rake_rate_adult'] = floatval(str_replace('R', '', $season['rake_rate_adult']));
            $season['rake_rate_child'] = floatval(str_replace('R', '', $season['rake_rate_child']));
            $season['rake_rate_guide'] = floatval(str_replace('R', '', $season['rake_rate_guide']));

            Season::updateOrCreate(['id' => $season['id']], $season);
        }
        $this->info('Seasons seeded successfully.');


        $inventory = [
            [
                'id' => 1,
                'allow_internal_override' => 1,
                'service_id' => 1,
                'season_id' => 1,

            ],
            [
                'id' => 2,
                'allow_internal_override' => 1,
                'service_id' => 2,
                'season_id' => 2,

            ],
            [
                'id' => 3,
                'allow_internal_override' => 1,
                'service_id' => 3,
                'season_id' => 3,

            ],
            [
                'id' => 4,
                'allow_internal_override' => 1,
                'service_id' => 4,
                'season_id' => 4,

            ],
            [
                'id' => 5,
                'allow_internal_override' => 1,
                'service_id' => 5,
                'season_id' => 5,

            ],
            [
                'id' => 6,
                'allow_internal_override' => 1,
                'service_id' => 6,
                'season_id' => 6,

            ],
            [
                'id' => 7,
                'allow_internal_override' => 1,
                'service_id' => 6,
                'season_id' => 7,

            ],
        ];

        foreach ($inventory as $inv) {
            Inventory::updateOrCreate(['id' => $inv['id']], $inv);
        }

        $inventory_time_slots = [
            [
                'id' => 1,
                'inventory_id' => 1,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 2,
                'inventory_id' => 1,
                'start_time' => '12:00:00',
            ],
            [
                'id' => 3,
                'inventory_id' => 2,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 4,
                'inventory_id' => 2,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 5,
                'inventory_id' => 3,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 6,
                'inventory_id' => 3,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 7,
                'inventory_id' => 4,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 8,
                'inventory_id' => 4,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 9,
                'inventory_id' => 5,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 10,
                'inventory_id' => 5,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 11,
                'inventory_id' => 6,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 12,
                'inventory_id' => 6,
                'start_time' => '10:00:00',
            ],
            [
                'id' => 13,
                'inventory_id' => 7,
                'start_time' => '08:00:00',
            ],
            [
                'id' => 14,
                'inventory_id' => 7,
                'start_time' => '10:00:00',
            ],
        ];

        foreach ($inventory_time_slots as $invTs) {
            InventoryTimeSlot::updateOrCreate(['id' => $invTs['id']], $invTs);
        }
        $this->info('InventoryTimeSlots seeded successfully.');
        $inventory_type_days = [
            ['id' => 1, 'inventory_id' => 1, 'type_day_id' => 1],
            ['id' => 2, 'inventory_id' => 1, 'type_day_id' => 2],
            ['id' => 3, 'inventory_id' => 1, 'type_day_id' => 3],
            ['id' => 4, 'inventory_id' => 1, 'type_day_id' => 4],
            ['id' => 5, 'inventory_id' => 1, 'type_day_id' => 5],
            ['id' => 6, 'inventory_id' => 1, 'type_day_id' => 6],
            ['id' => 7, 'inventory_id' => 1, 'type_day_id' => 7],
            ['id' => 8, 'inventory_id' => 2, 'type_day_id' => 1],
            ['id' => 9, 'inventory_id' => 2, 'type_day_id' => 2],
            ['id' => 10, 'inventory_id' => 2, 'type_day_id' => 3],
            ['id' => 11, 'inventory_id' => 2, 'type_day_id' => 4],
            ['id' => 12, 'inventory_id' => 2, 'type_day_id' => 5],
            ['id' => 13, 'inventory_id' => 2, 'type_day_id' => 6],
            ['id' => 14, 'inventory_id' => 2, 'type_day_id' => 7],
            ['id' => 15, 'inventory_id' => 3, 'type_day_id' => 1],
            ['id' => 16, 'inventory_id' => 3, 'type_day_id' => 2],
            ['id' => 17, 'inventory_id' => 3, 'type_day_id' => 3],
            ['id' => 18, 'inventory_id' => 3, 'type_day_id' => 4],
            ['id' => 19, 'inventory_id' => 3, 'type_day_id' => 5],
            ['id' => 20, 'inventory_id' => 3, 'type_day_id' => 6],
            ['id' => 21, 'inventory_id' => 3, 'type_day_id' => 7],
            ['id' => 22, 'inventory_id' => 4, 'type_day_id' => 1],
            ['id' => 23, 'inventory_id' => 4, 'type_day_id' => 2],
            ['id' => 24, 'inventory_id' => 4, 'type_day_id' => 3],
            ['id' => 25, 'inventory_id' => 4, 'type_day_id' => 4],
            ['id' => 26, 'inventory_id' => 4, 'type_day_id' => 5],
            ['id' => 27, 'inventory_id' => 4, 'type_day_id' => 6],
            ['id' => 28, 'inventory_id' => 4, 'type_day_id' => 7],
            ['id' => 29, 'inventory_id' => 5, 'type_day_id' => 1],
            ['id' => 30, 'inventory_id' => 5, 'type_day_id' => 2],
            ['id' => 31, 'inventory_id' => 5, 'type_day_id' => 3],
            ['id' => 32, 'inventory_id' => 5, 'type_day_id' => 4],
            ['id' => 33, 'inventory_id' => 5, 'type_day_id' => 5],
            ['id' => 34, 'inventory_id' => 5, 'type_day_id' => 6],
            ['id' => 35, 'inventory_id' => 5, 'type_day_id' => 7],
            ['id' => 36, 'inventory_id' => 6, 'type_day_id' => 1],
            ['id' => 37, 'inventory_id' => 6, 'type_day_id' => 2],
            ['id' => 38, 'inventory_id' => 6, 'type_day_id' => 3],
            ['id' => 39, 'inventory_id' => 6, 'type_day_id' => 4],
            ['id' => 40, 'inventory_id' => 6, 'type_day_id' => 5],
            ['id' => 41, 'inventory_id' => 6, 'type_day_id' => 6],
            ['id' => 42, 'inventory_id' => 6, 'type_day_id' => 7],
        ];
        foreach ($inventory_type_days as $invTypeDay) {
            InventoryTypeDay::updateOrCreate(['id' => $invTypeDay['id']], $invTypeDay);
        }

        $closeouts = [
            [
                'id' => 1,
                'service_id' => 1,
                'season_id' => 1,
                'name' => 'renovations',
                'start_date' => '2025-06-20',
                'end_date' => '2025-06-27',
                'reason' => 'general maintenance',
            ],
            [
                'id' => 2,
                'service_id' => 1,
                'season_id' => 1,
                'name' => 'holiday',
                'start_date' => '2025-07-25',
                'end_date' => '2025-07-26',
                'reason' => 'general',
            ],
            [
                'id' => 3,
                'service_id' => 2,
                'season_id' => 2,
                'name' => 'holiday',
                'start_date' => '2025-12-25',
                'end_date' => '2025-12-26',
                'reason' => 'general',
            ],
        ];

        foreach ($closeouts as $closeout) {
            Closeout::updateOrCreate(['id' => $closeout['id']], $closeout);
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        $closeoutDayTimeSlots = [
            [
                'id' => 1,
                'closeout_id' => 1,
                'day_id' => 6,
                'time_slot_id' => 1,
            ],
            [
                'id' => 2,
                'closeout_id' => 1,
                'day_id' => 7,
                'time_slot_id' => 2,
            ],
            [
                'id' => 3,
                'closeout_id' => 2,
                'day_id' => 6,
                'time_slot_id' => 3,
            ],
            [
                'id' => 4,
                'closeout_id' => 2,
                'day_id' => 7,
                'time_slot_id' => 4,
            ],
            [
                'id' => 5,
                'closeout_id' => 3,
                'day_id' => 7,
                'time_slot_id' => 5,
            ]
        ];

        foreach ($closeoutDayTimeSlots as $closeoutDayTimeSlot) {
            CloseoutDayTimeSlot::updateOrCreate(['id' => $closeoutDayTimeSlot['id']], $closeoutDayTimeSlot);
        }

        $customers = [
            [
                'id'             => 1,
                'full_name'      => 'Customer1',
                'email_address'  => 'Customer1@email.com',
                'contact_number' => '1234567890',
                'nationality_id'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 2,
                'full_name'           => 'Customer2',
                'email_address'          => 'Customer2@email.com',
                'contact_number' => '0987654321',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 3,
                'full_name'           => 'Customer3',
                'email_address'          => 'Customer3@email.com',
                'contact_number' => '9876543210',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 4,
                'full_name'           => 'Customer4',
                'email_address'          => 'Customer4@email.com',
                'contact_number' => '9876543211',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 5,
                'full_name'           => 'Customer5',
                'email_address'          => 'Customer5@email.com',
                'contact_number' => '9876543212',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 6,
                'full_name'           => 'Customer6',
                'email_address'          => 'Customer6@email.com',
                'contact_number' => '9876543213',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 7,
                'full_name'           => 'Customer7',
                'email_address'          => 'Customer7@email.com',
                'contact_number' => '9876543214',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 8,
                'full_name'           => 'Customer8',
                'email_address'          => 'Customer8@email.com',
                'contact_number' => '9876543215',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 9,
                'full_name'           => 'Customer9',
                'email_address'          => 'Customer9@email.com',
                'contact_number' => '9876543216',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
            [
                'id'             => 10,
                'full_name'           => 'Customer10',
                'email_address'          => 'Customer10@email.com',
                'contact_number' => '9876543217',
                'nationality'    => '',
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['id' => $customer['id']], $customer);
        }



        $bookings = [
            [
                'id'             => 1,
                'lead_name'      => 'Customer1',
                'user_id'        => 1,
                'status_booking_id' => 1,
                'is_sms'         => 0,
                'is_corporate'   => 0,
                'due_date'       => '2025-03-01',
                'tot_paid'       => 0,
                'grand_tot_due'  => 1700.00,
                'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                'service_id'     => 1,
                'season_id'      => 1,
                'inventory_id'   => 1,
                'updated_by_id'  => 1,
                'customers'      => [1, 2, 3],
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 2,
                'lead_name'      => 'Customer4',
                'user_id'        => 1,
                'status_booking_id' => 1,
                'is_sms'         => 0,
                'is_corporate'   => 0,
                'due_date'       => '2025-04-02',
                'tot_paid'       => 0,
                'grand_tot_due'  => 2000.00,
                'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                'service_id'     => 1,
                'season_id'      => 1,
                'inventory_id'   => 1,
                'updated_by_id'  => 1,
                'customers'      => [4, 5, 6],
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 3,
                'lead_name'      => 'Customer7',
                'user_id'        => 1,
                'status_booking_id' => 1,
                'is_sms'         => 0,
                'is_corporate'   => 0,
                'due_date'       => '2025-07-03',
                'tot_paid'       => 0,
                'grand_tot_due'  => 1100.00,
                'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                'service_id'     => 1,
                'season_id'      => 1,
                'inventory_id'   => 1,
                'updated_by_id'  => 1,
                'customers'      => [7, 8],
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 4,
                'lead_name'      => 'Customer9',
                'user_id'        => 1,
                'status_booking_id' => 1,
                'is_sms'         => 0,
                'is_corporate'   => 0,
                'due_date'       => '2025-07-04',
                'tot_paid'       => 0,
                'grand_tot_due'  => 5000.00,
                'reference'      => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                'service_id'     => 1,
                'season_id'      => 1,
                'inventory_id'   => 1,
                'updated_by_id'  => 1,
                'customers'      => [9, 10],
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
        ];

        foreach ($bookings as $item) {
            $booking =  Booking::updateOrCreate(['id' => $item['id']], $item);
            // Prepare pivot data
            $pivotData = [];

            foreach ($item['customers'] as $index => $customerId) {
                $pivotData[$customerId] = [
                    'team_id' => $item['team_id'],         // Or use logic to assign different teams
                    'is_lead' => $index === 0 ? 1 : 0,     // First customer is lead, rest are not
                ];
            }

            // Sync with pivot data
            $booking->customers()->sync($pivotData);
            //$booking->customers()->sync($item['customers']);
        }

        $customerManifests = [
            [
                'id'             => 1,
                'position'       => '1',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 1,
                'booking_id'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 2,
                'position'       => '2',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 2,
                'booking_id'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 3,
                'position'       => '3',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 3,
                'booking_id'     => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 4,
                'position'       => '1',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 4,
                'booking_id'     => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 5,
                'position'       => '2',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 5,
                'booking_id'     => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 6,
                'position'       => '3',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 6,
                'booking_id'     => 2,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 7,
                'position'       => '1',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 7,
                'booking_id'     => 3,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 8,
                'position'       => '2',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 8,
                'booking_id'     => 3,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 9,
                'position'       => '1',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 9,
                'booking_id'     => 4,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 10,
                'position'       => '2',
                'notes'          => '',
                'is_indemnity'   => 0,
                'customer_id'    => 10,
                'booking_id'     => 4,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
        ];

        foreach ($customerManifests as $customerManifest) {
            CustomerManifest::updateOrCreate(['id' => $customerManifest['id']], $customerManifest);
        }


        $bookingDetails = [
            [
                'id'             => 1,
                'booking_id'     => 1,
                'is_wholesaler_booking' => 0,
                'is_needs_pickup' => 0,
                'is_web_booking' => 0,
                'is_videos_photos' => 0,
                'is_exclusive_resource_use' => 0,
                'is_override_time_slots' => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 2,
                'booking_id'     => 2,
                'is_wholesaler_booking' => 0,
                'is_needs_pickup' => 0,
                'is_web_booking' => 0,
                'is_videos_photos' => 0,
                'is_exclusive_resource_use' => 0,
                'is_override_time_slots' => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 3,
                'booking_id'     => 3,
                'is_wholesaler_booking' => 0,
                'is_needs_pickup' => 0,
                'is_web_booking' => 0,
                'is_videos_photos' => 0,
                'is_exclusive_resource_use' => 0,
                'is_override_time_slots' => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 4,
                'booking_id'     => 4,
                'is_wholesaler_booking' => 0,
                'is_needs_pickup' => 0,
                'is_web_booking' => 0,
                'is_videos_photos' => 0,
                'is_exclusive_resource_use' => 0,
                'is_override_time_slots' => 0,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
        ];

        foreach ($bookingDetails as $bookingDetail) {
            BookingDetail::updateOrCreate(['id' => $bookingDetail['id']], $bookingDetail);
        }

        $bookingInventories = [
            [
                'id'             => 1,
                'booking_id'     => 1,
                'booking_detail_id' => 1,
                'date'           => '2025-03-01',
                'tot_adults'     => 2,
                'tot_children'   => 1,
                'tot_guides'     => 1,
                'pax'            => 3,
                'inventory_type_day_id' => 7,
                'inventory_time_slot_id' => 1,
                'inventory_override_id' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => 7,
            ],
            [
                'id'             => 2,
                'booking_id'     => 2,
                'booking_detail_id' => 2,
                'date'           => '2025-04-02',
                'tot_adults'     => 3,
                'tot_children'   => 0,
                'tot_guides'     => 1,
                'pax'            => 3,
                'inventory_type_day_id' => 1,
                'inventory_time_slot_id' => 2,
                'inventory_override_id' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => 7,
            ],
            [
                'id'             => 3,
                'booking_id'     => 3,
                'booking_detail_id' => 3,
                'date'           => '2025-07-03',
                'tot_adults'     => 1,
                'tot_children'   => 1,
                'tot_guides'     => 1,
                'pax'            => 2,
                'inventory_type_day_id' => 2,
                'inventory_time_slot_id' => 2,
                'inventory_override_id' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => 7,
            ],
            [
                'id'             => 4,
                'booking_id'     => 4,
                'booking_detail_id' => 4,
                'date'           => '2025-07-04',
                'tot_adults'     => 8,
                'tot_children'   => 0,
                'tot_guides'     => 1,
                'pax'            => 8,
                'inventory_type_day_id' => 3,
                'inventory_time_slot_id' => 1,
                'inventory_override_id' => null,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => 7,
            ],
        ];

        foreach ($bookingInventories as $bookingInventory) {
            BookingInventory::updateOrCreate(['id' => $bookingInventory['id']], $bookingInventory);
        }

        $bookingFees = [
            [
                'id'             => 1,
                'booking_id'     => 1,
                'booking_detail_id' => 1,
                'tot_adults'     => 1200.00,
                'tot_children'   => 300.00,
                'tot_guides'     => 200.00,
                'is_voucher'     => 0,
                'voucher_code'   => '',
                'invoice_note'   => '',
                'is_email_reminder' => 0,
                'booking_note'   => '',
                'tot_amount'     => 1700.00,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 2,
                'booking_id'     => 2,
                'booking_detail_id' => 2,
                'tot_adults'     => 1800.00,
                'tot_children'   => 0,
                'tot_guides'     => 200.00,
                'is_voucher'     => 0,
                'voucher_code'   => '',
                'invoice_note'   => '',
                'is_email_reminder' => 0,
                'booking_note'   => '',
                'tot_amount'     => 2000.00,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 3,
                'booking_id'     => 3,
                'booking_detail_id' => 3,
                'tot_adults'     => 600.00,
                'tot_children'   => 300.00,
                'tot_guides'     => 200.00,
                'is_voucher'     => 0,
                'voucher_code'   => '',
                'invoice_note'   => '',
                'is_email_reminder' => 0,
                'booking_note'   => '',
                'tot_amount'     => 1100.00,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 4,
                'booking_id'     => 4,
                'booking_detail_id' => 4,
                'tot_adults'     => 4800.00,
                'tot_children'   => 0,
                'tot_guides'     => 200.00,
                'is_voucher'     => 0,
                'voucher_code'   => '',
                'invoice_note'   => '',
                'is_email_reminder' => 0,
                'booking_note'   => '',
                'tot_amount'     => 5000.00,
                'created_at'     => now(),
                'updated_at'     => now(),
                'deleted_at'     => null,
                'team_id'        => null,
            ],
        ];
        foreach ($bookingFees as $bookingFee) {
            BookingFee::updateOrCreate(['id' => $bookingFee['id']], $bookingFee);
        }

        $bookingPayments = [
            [
                'id'             => 1,
                'booking_id'     => 1,
                'type_pay_option_id'   => 1,     // or 'Pay Offline'
                'type_payment_id' => 1, // Cash // 5 eft
                'status_payment_id'         => 1,        // 'paid', 'failed', 'cancelled'
                'amount'         => 1700.00,          // full amount due or partial
                'reference'      => $bookings[0]['reference'], //  'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                'gateway_id'        => 1,        // 'Manual'
                'meta'           => json_encode([
                    'file_path' => '/assets/icons/plus.svg',
                    'note' => '',
                    'original_name' => '',
                ]),
                'payment_date'   =>  now(),
                'redeemer_name'  => '',
                'voucher_code' => null,
                'note'           => '',
                'updated_by_id'  => null,
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 2,
                'booking_id'     => 2,
                'type_pay_option_id'   => 1,     // or 'Pay Offline'
                'type_payment_id' => 1, // Cash // 5 eft
                'status_payment_id'         => 1,        // 'paid', 'failed', 'cancelled'
                'amount'         => 1000.00,          // full amount due or partial
                'reference'      => $bookings[1]['reference'], // 'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                'gateway_id'        => 1,        // 'Manual'
                'meta'           => json_encode([
                    'file_path' => '/assets/icons/plus.svg',
                    'note' => '',
                    'original_name' => '',
                ]),
                'payment_date'   =>  now(),
                'redeemer_name'  => '',
                'voucher_code' => null,
                'note'           => '',
                'updated_by_id'  => null,
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 3,
                'booking_id'     => 3,
                'type_pay_option_id'   => 1,     // or 'Pay Offline'
                'type_payment_id' => 1, // Cash // 5 eft
                'status_payment_id'         => 1,        // 'paid', 'failed', 'cancelled'
                'amount'         => 500.00,          // full amount due or partial
                'reference'      => $bookings[2]['reference'], //'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                'gateway_id'        => 1,        // 'Manual'
                'meta'           => json_encode([
                    'file_path' => '/assets/icons/plus.svg',
                    'note' => '',
                    'original_name' => '',
                ]),
                'payment_date'   =>  now(),
                'redeemer_name'  => '',
                'voucher_code' => null,
                'note'           => '',
                'updated_by_id'  => null,
                'deleted_at'     => null,
                'team_id'        => null,
            ],
            [
                'id'             => 4,
                'booking_id'     => 4,
                'type_pay_option_id'   => 1,     // or 'Pay Offline'
                'type_payment_id' => 1, // Cash // 5 eft
                'status_payment_id'         => 1,        // 'paid', 'failed', 'cancelled'
                'amount'         => 3000.00,          // full amount due or partial
                'reference'      => $bookings[3]['reference'], //'OFF-' . strtoupper(Str::random(6)), // can be order ref or external tx
                'gateway_id'        => 1,        // 'Manual'
                'meta'           => json_encode([
                    'file_path' => '/assets/icons/plus.svg',
                    'note' => '',
                    'original_name' => '',
                ]),
                'payment_date'   =>  now(),
                'redeemer_name'  => '',
                'voucher_code' => null,
                'note'           => '',
                'updated_by_id'  => null,
                'deleted_at'     => null,
                'team_id'        => null,
            ],
        ];
        foreach ($bookingPayments as $bookingPayment) {
            $booking = Booking::findOrFail($bookingPayment['booking_id']);

            // Mark as paid (in real app, you'd validate PayFast response)
            $booking->update([
                'status_booking_id' => 2, // e.g., "paid"
                'tot_paid' => $bookingPayment['amount'],
            ]);


            BookingPayment::updateOrCreate(['id' => $bookingPayment['id']], $bookingPayment);
        }



        $this->info('Core data seeded successfully.');

        // php artisan seed:core-data
    }
}
