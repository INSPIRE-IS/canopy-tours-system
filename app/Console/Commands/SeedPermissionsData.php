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

        $directorFilterPermissions = Permission::whereNotIn(
            'name',
            ['audit_logs_access', 'booking_questionnaires_access', 'teams_create']
        )->get();
        $directorPermissions = $directorFilterPermissions->filter(
            fn($p) =>
            !str_starts_with($p->name, 'user_') &&
                !str_starts_with($p->name, 'role_') &&
                !str_starts_with($p->name, 'permission_')
        );
        Role::findOrFail(2)->permissions()->sync($directorPermissions->pluck('id'));

        $managerFilterPermissions = Permission::whereNotIn(
            'name',
            ['audit_logs_access', 'booking_questionnaires_access', 'invoice_templates_access', 'help_questions_access', 'user_alerts_access', 'vouchers_access', 'teams_create']
        )->get();
        $managerPermissions = $managerFilterPermissions->filter(
            fn($p) =>
            !str_starts_with($p->name, 'user_') &&
                !str_starts_with($p->name, 'role_') &&
                !str_starts_with($p->name, 'permission_')
        );
        Role::findOrFail(3)->permissions()->sync($managerPermissions->pluck('id'));

        $userFilterPermissions = Permission::whereNotIn('name', ['audit_logs_access', 'booking_questionnaires_access', 'invoice_templates_access', 'help_questions_access', 'user_alerts_access', 'vouchers_access', 'teams_create'])->get();
        $userPermissions = $userFilterPermissions->filter(
            fn($p) =>
            !str_starts_with($p->name, 'user_') &&
                !str_starts_with($p->name, 'role_') &&
                !str_starts_with($p->name, 'permission_')
        );
        Role::findOrFail(4)->permissions()->sync($userPermissions->pluck('id'));
        Role::findOrFail(5)->permissions()->sync($userPermissions->pluck('id'));


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
