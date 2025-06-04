<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users_and_teams_tables', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('team_id')->nullable()->after('id');
                $table->foreign('team_id')->references('id')->on('teams')->onDelete('set null');
            });
    //  $table->foreignId('team_id')->constrained('teams');
    // $table->foreignId('owner_id')->constrained('users');
            Schema::table('teams', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('id');
                $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_and_teams_tables', function (Blueprint $table) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            });
    
            Schema::table('teams', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        });
    }
};
