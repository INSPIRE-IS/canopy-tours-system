<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\TypeDay;
use App\Models\TypePayOption;
use App\Models\TypePayment;
use App\Models\StatusBooking;
use App\Models\TypeAffiliate;
use App\Models\TypeGroup;

class SeedCoreData extends Command
{
    protected $signature = 'seed:core-data'; // php artisan seed:core-data
    protected $description = 'Create or update core seed data for permissions, roles, etc.';

    public function handle()
    {
        $this->info('Seeding core data...');

        $permissions = [
            [
                'id'    => 1,
                'name' => 'user_management_access',
            ],
            [
                'id'    => 2,
                'name' => 'permission_create',
            ],
            [
                'id'    => 3,
                'name' => 'permission_edit',
            ],
            [
                'id'    => 4,
                'name' => 'permission_show',
            ],
            [
                'id'    => 5,
                'name' => 'permission_delete',
            ],
            [
                'id'    => 6,
                'name' => 'permission_access',
            ],
            [
                'id'    => 7,
                'name' => 'role_create',
            ],
            [
                'id'    => 8,
                'name' => 'role_edit',
            ],
            [
                'id'    => 9,
                'name' => 'role_show',
            ],
            [
                'id'    => 10,
                'name' => 'role_delete',
            ],
            [
                'id'    => 11,
                'name' => 'role_access',
            ],
            [
                'id'    => 12,
                'name' => 'user_create',
            ],
            [
                'id'    => 13,
                'name' => 'user_edit',
            ],
            [
                'id'    => 14,
                'name' => 'user_show',
            ],
            [
                'id'    => 15,
                'name' => 'user_delete',
            ],
            [
                'id'    => 16,
                'name' => 'user_access',
            ],
            [
                'id'    => 17,
                'name' => 'audit_log_show',
            ],
            [
                'id'    => 18,
                'name' => 'audit_log_access',
            ],
            [
                'id'    => 19,
                'name' => 'team_create',
            ],
            [
                'id'    => 20,
                'name' => 'team_edit',
            ],
            [
                'id'    => 21,
                'name' => 'team_show',
            ],
            [
                'id'    => 22,
                'name' => 'team_delete',
            ],
            [
                'id'    => 23,
                'name' => 'team_access',
            ],
            [
                'id'    => 24,
                'name' => 'service_access',
            ],
            [
                'id'    => 25,
                'name' => 'service_create',
            ],
            [
                'id'    => 26,
                'name' => 'service_edit',
            ],
            [
                'id'    => 27,
                'name' => 'service_show',
            ],
            [
                'id'    => 28,
                'name' => 'service_delete',
            ],
            [
                'id'    => 29,
                'name' => 'season_access',
            ],
            [
                'id'    => 30,
                'name' => 'season_create',
            ],
            [
                'id'    => 31,
                'name' => 'season_edit',
            ],
            [
                'id'    => 32,
                'name' => 'season_show',
            ],
            [
                'id'    => 33,
                'name' => 'season_delete',
            ],
            [
                'id'    => 34,
                'name' => 'inventory_access',
            ],
            [
                'id'    => 35,
                'name' => 'inventory_create',
            ],
            [
                'id'    => 36,
                'name' => 'inventory_edit',
            ],
            [
                'id'    => 37,
                'name' => 'inventory_show',
            ],
            [
                'id'    => 38,
                'name' => 'inventory_delete',
            ],
            [
                'id'    => 39,
                'name' => 'task_access',
            ],
            [
                'id'    => 40,
                'name' => 'tasks_calendar_access',
            ],
            [
                'id'    => 41,
                'name' => 'profile_password_edit',
            ],
        ];
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['id' => $permission['id']], $permission);
        }

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
            TypeAffiliate::updateOrCreate(['id' => $affiliateType['id']], $affiliateType);
        }

        $this->info('Core data seeded successfully.');
    }
}
