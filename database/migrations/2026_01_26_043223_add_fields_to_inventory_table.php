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
        Schema::table('inventories', function (Blueprint $table) {
            $table->integer('slot_cutoff_hours')->default(0); // no hours before a slot closes 0 no cutoff
            $table->integer('slot_funneling')->default(0); // number of open slots allowed 0 disabled
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('slot_cutoff_hours');
            $table->dropColumn('slot_funneling');
        });
    }
};
