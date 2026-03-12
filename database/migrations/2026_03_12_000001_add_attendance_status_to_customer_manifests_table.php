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
        Schema::table('customer_manifests', function (Blueprint $table) {
            $table->string('attendance_status')
                ->default('no_show')
                ->after('is_indemnity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_manifests', function (Blueprint $table) {
            $table->dropColumn('attendance_status');
        });
    }
};
