<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'integration_allowed_team_ids')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('integration_allowed_team_ids')->nullable();
            });
        }

        DB::table('users')
            ->join('teams', 'teams.id', '=', 'users.team_id')
            ->whereNull('teams.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('users.integration_allowed_team_ids')
            ->whereExists(function ($query) {
                $query->selectRaw('1')->from('role_user')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->whereColumn('role_user.user_id', 'users.id')
                    ->where('roles.name', 'Integration API')
                    ->whereNull('roles.deleted_at');
            })
            ->select('users.id', 'users.team_id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)
                        ->update(['integration_allowed_team_ids' => json_encode([(int) $user->team_id])]);
                }
            }, 'users.id', 'id');
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('integration_allowed_team_ids'));
    }
};
