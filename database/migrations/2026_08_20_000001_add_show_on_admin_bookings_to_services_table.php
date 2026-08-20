<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('services', 'show_on_admin_bookings')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('show_on_admin_bookings')
                ->default(true)
                ->after(Schema::hasColumn('services', 'is_online_bookable') ? 'is_online_bookable' : 'allow_internal_capacity_override');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('services', 'show_on_admin_bookings')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('show_on_admin_bookings');
        });
    }
};
