<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $now = now();
        $permissionId = $this->restoreOrCreate('permissions', 'integration_api_docs_access', $now);
        $roleId = $this->restoreOrCreate('roles', 'Integration API', $now);

        DB::table('permission_role')->updateOrInsert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ], []);

        $adminRoleId = DB::table('roles')
            ->where('name', 'Admin')
            ->whereNull('deleted_at')
            ->value('id');

        if ($adminRoleId) {
            DB::table('permission_role')->updateOrInsert([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
            ], []);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'integration_api_docs_access')
            ->value('id');
        $roleId = DB::table('roles')
            ->where('name', 'Integration API')
            ->value('id');

        if ($permissionId) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        }

        if ($roleId && Schema::hasTable('role_user')) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
        }

        if ($roleId) {
            DB::table('roles')->where('id', $roleId)->delete();
        }

        if ($permissionId) {
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }

    private function restoreOrCreate(string $table, string $name, $now): int
    {
        $row = DB::table($table)->where('name', $name)->first();

        if ($row) {
            DB::table($table)->where('id', $row->id)->update([
                'deleted_at' => null,
                'updated_at' => $now,
            ]);

            return (int) $row->id;
        }

        return (int) DB::table($table)->insertGetId([
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);
    }
};
