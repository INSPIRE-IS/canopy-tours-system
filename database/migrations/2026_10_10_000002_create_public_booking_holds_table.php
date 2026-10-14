<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('frontend_hold_enabled')->default(true)->after('is_published');
            $table->unsignedSmallInteger('frontend_hold_minutes')->default(15)->after('frontend_hold_enabled');
        });

        Schema::create('public_booking_holds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('session_id', 100);
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('inventory_time_slot_id')->constrained('inventory_time_slots')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->date('booking_date');
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(
                ['inventory_time_slot_id', 'booking_date', 'expires_at'],
                'public_holds_slot_date_expiry_idx'
            );
            $table->index(
                ['session_id', 'team_id', 'completed_at', 'cancelled_at'],
                'public_holds_session_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_booking_holds');

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['frontend_hold_enabled', 'frontend_hold_minutes']);
        });
    }
};
