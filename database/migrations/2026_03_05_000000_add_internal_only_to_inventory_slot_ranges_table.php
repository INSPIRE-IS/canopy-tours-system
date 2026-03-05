<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_slot_ranges', function (Blueprint $table) {
            $table->boolean('internal_only')->default(false)->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_slot_ranges', function (Blueprint $table) {
            $table->dropColumn('internal_only');
        });
    }
};
