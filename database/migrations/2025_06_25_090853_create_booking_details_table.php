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
        Schema::create('booking_details', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_wholesaler_booking')->default(0)->nullable();
            $table->boolean('is_needs_pickup')->default(0)->nullable();
            $table->boolean('is_web_booking')->default(0)->nullable();
            $table->boolean('is_videos_photos')->default(0)->nullable();
            $table->boolean('is_exclusive_resource_use')->default(0)->nullable();
            $table->boolean('is_override_time_slots')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_details');
    }
};
