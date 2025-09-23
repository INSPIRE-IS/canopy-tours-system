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
        Schema::create('wholesaler_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('consultant_name')->nullable();
            $table->string('consultant_email')->nullable();
            $table->string('consultant_mobile')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('wholesaler_id')->constrained('wholesalers');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wholesaler_bookings');
    }
};
