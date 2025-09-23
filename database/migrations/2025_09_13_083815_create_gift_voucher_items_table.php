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
        Schema::create('gift_voucher_items', function (Blueprint $table) {
            $table->id();
            // Pax counts
            $table->unsignedInteger('pax_adults')->default(0);
            $table->unsignedInteger('pax_children')->default(0);
            // Pricing snapshot
            $table->decimal('price_adult', 10, 2)->default(0);
            $table->decimal('price_child', 10, 2)->default(0);

            $table->foreignId('gift_voucher_id')->nullable()->constrained('gift_vouchers');
            $table->foreignId('booking_id')->nullable()->constrained('bookings');
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->foreignId('season_id')->nullable()->constrained('seasons');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_voucher_items');
    }
};
