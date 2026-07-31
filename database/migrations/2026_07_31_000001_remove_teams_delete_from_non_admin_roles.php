<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'teams_delete')
            ->value('id');

        if (!$permissionId) {
            return;
        }

        DB::table('permission_role')
            ->where('permission_id', $permissionId)
            ->where('role_id', '!=', 1)
            ->delete();
    }

    public function down(): void
    {
        // Intentionally do not restore site/team delete access to non-admin roles.
    }
};
