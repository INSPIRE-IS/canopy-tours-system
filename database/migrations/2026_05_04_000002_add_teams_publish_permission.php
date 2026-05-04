<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissionRow = DB::table('permissions')
            ->where('name', 'teams_publish')
            ->first();

        if (!$permissionRow) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'teams_publish',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        } else {
            $permissionId = $permissionRow->id;

            // If the permission exists but was soft-deleted, restore it.
            if (property_exists($permissionRow, 'deleted_at') && $permissionRow->deleted_at) {
                DB::table('permissions')
                    ->where('id', $permissionId)
                    ->update([
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
            }
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('permission_role')) {
            return;
        }

        // Grant the capability to Admin/Director/Manager without detaching any existing permissions.
        $roleIds = DB::table('roles')
            ->whereIn('id', [1, 2, 3])
            ->pluck('id')
            ->all();

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ], []);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'teams_publish')
            ->value('id');

        if (!$permissionId) {
            return;
        }

        DB::table('permission_role')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', [1, 2, 3])
            ->delete();

        // Keep the permission row to avoid breaking references; it may be assigned to other roles.
    }
};

