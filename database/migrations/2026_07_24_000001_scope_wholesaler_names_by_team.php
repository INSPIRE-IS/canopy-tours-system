<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesalers', function (Blueprint $table) {
            $table->dropUnique('wholesalers_name_unique');
            $table->unique(['team_id', 'name'], 'wholesalers_team_id_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wholesalers', function (Blueprint $table) {
            $table->dropUnique('wholesalers_team_id_name_unique');
            $table->unique('name', 'wholesalers_name_unique');
        });
    }
};
