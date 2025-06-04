<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Permission;
use App\Models\Role;
use App\Models\StatusBooking;
use App\Models\Team;
use App\Models\TypeDay;
use App\Models\TypePayment;
use App\Models\TypePayOption;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
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
                'name' => 'task_management_access',
            ],
            [
                'id'    => 25,
                'name' => 'task_status_create',
            ],
            [
                'id'    => 26,
                'name' => 'task_status_edit',
            ],
            [
                'id'    => 27,
                'name' => 'task_status_show',
            ],
            [
                'id'    => 28,
                'name' => 'task_status_delete',
            ],
            [
                'id'    => 29,
                'name' => 'task_status_access',
            ],
            [
                'id'    => 30,
                'name' => 'task_tag_create',
            ],
            [
                'id'    => 31,
                'name' => 'task_tag_edit',
            ],
            [
                'id'    => 32,
                'name' => 'task_tag_show',
            ],
            [
                'id'    => 33,
                'name' => 'task_tag_delete',
            ],
            [
                'id'    => 34,
                'name' => 'task_tag_access',
            ],
            [
                'id'    => 35,
                'name' => 'task_create',
            ],
            [
                'id'    => 36,
                'name' => 'task_edit',
            ],
            [
                'id'    => 37,
                'name' => 'task_show',
            ],
            [
                'id'    => 38,
                'name' => 'task_delete',
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

        Permission::insert($permissions);

        $roles = [
            [
                'id'    => 1,
                'name' => 'Admin',
            ],
            [
                'id'    => 2,
                'name' => 'User',
            ],
        ];

        Role::insert($roles);

        $admin_permissions = Permission::all();
        Role::findOrFail(1)->permissions()->sync($admin_permissions->pluck('id'));

        $user_permissions = $admin_permissions->filter(function ($permission) {
            return substr($permission->name, 0, 5) != 'user_' && substr($permission->name, 0, 5) != 'role_' && substr($permission->name, 0, 11) != 'permission_';
        });
        Role::findOrFail(2)->permissions()->sync($user_permissions);

        $teams = [
            [
                'id'    => 1,
                'name' => 'team1',
            ],
        ];
        Team::insert($teams);

        $users = [
            [
                'id'             => 1,
                'name'           => 'Admin',
                'surname'        => 'Admin',
                'email'          => 'admin@Admin.com',
                'password'       => bcrypt('password'),
                'mobile_number'  => '',
                'team_id'        => null,
                'is_admin'       => true,
                'is_email_recipient' => false,
                'remember_token' => null,
            ],
            [
                'id'             => 2,
                'name'           => 'User',
                'surname'        => 'User',
                'email'          => 'user@user.com',
                'password'       => bcrypt('password'),
                'mobile_number'  => '',
                'team_id'        => 1,
                'is_admin'       => false,
                'is_email_recipient' => false,
                'remember_token' => null,
            ],
        ];

        User::insert($users);
        User::findOrFail(1)->roles()->sync(1);
        User::findOrFail(2)->roles()->sync(2);


        $type_days = [
            [
                'id'    => 1,
                'name' => 'm',
            ],
            [
                'id'    => 2,
                'name' => 't',
            ],
            [
                'id'    => 3,
                'name' => 'w',
            ],
            [
                'id'    => 4,
                'name' => 't',
            ],
            [
                'id'    => 5,
                'name' => 'f',
            ],
            [
                'id'    => 6,
                'name' => 's',
            ],
            [
                'id'    => 7,
                'name' => 's',
            ],
        ];
        TypeDay::insert($type_days);

        $type_pay_options = [
            [
                'id'    => 1,
                'name' => 'Pay Offline',
            ],
            [
                'id'    => 2,
                'name' => 'Pay OnLine',
            ],
            [
                'id'    => 3,
                'name' => 'Split Payments',
            ],
        ];
        TypePayOption::insert($type_pay_options);

        $type_payments = [
            [
                'id'    => 1,
                'name' => 'Cash',
            ],
            [
                'id'    => 2,
                'name' => 'Cheque',
            ],
            [
                'id'    => 3,
                'name' => 'Credit Card',
            ],
            [
                'id'    => 4,
                'name' => 'Debit Card',
            ],
            [
                'id'    => 5,
                'name' => 'Direct Deposit / EFT',
            ],
            [
                'id'    => 6,
                'name' => 'Masterpass',
            ],
            [
                'id'    => 7,
                'name' => 'Paypal',
            ],
            [
                'id'    => 8,
                'name' => 'Snapscan',
            ],
            [
                'id'    => 9,
                'name' => 'Zapper',
            ],
            [
                'id'    => 10,
                'name' => 'Voucher',
            ],
        ];
        TypePayment::insert($type_payments);

        $status_bookings = [
            [
                'id'    => 1,
                'name' => 'quote',
            ],
            [
                'id'    => 2,
                'name' => 'invoice',
            ],
            [
                'id'    => 3,
                'name' => 'sent',
            ],
            [
                'id'    => 4,
                'name' => 'paid',
            ],
            [
                'id'    => 5,
                'name' => 'unpaid',
            ],
            [
                'id'    => 6,
                'name' => 'cancelled',
            ],
        ];
        StatusBooking::insert($status_bookings);
    }
}
