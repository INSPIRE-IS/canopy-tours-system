<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (!Schema::hasColumn('teams', 'frontend_unpaid_booking_hold_hours')) {
                $table->unsignedTinyInteger('frontend_unpaid_booking_hold_hours')
                    ->nullable()
                    ->after('is_published');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'frontend_unpaid_booking_hold_hours')) {
                $table->dropColumn('frontend_unpaid_booking_hold_hours');
            }
        });
    }
};
