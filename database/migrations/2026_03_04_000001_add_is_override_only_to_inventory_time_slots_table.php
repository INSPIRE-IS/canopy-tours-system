<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_time_slots', function (Blueprint $table) {
            $table->boolean('is_override_only')->default(false)->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_time_slots', function (Blueprint $table) {
            $table->dropColumn('is_override_only');
        });
    }
};
