<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('teams', 'frontend_unpaid_booking_expiry_hours')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->unsignedInteger('frontend_unpaid_booking_expiry_hours')
                    ->nullable()
                    ->after('frontend_hold_minutes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teams', 'frontend_unpaid_booking_expiry_hours')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('frontend_unpaid_booking_expiry_hours');
            });
        }
    }
};
