<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_pricing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('wholesaler_rate_id')->nullable();
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->decimal('final_price_adult', 10, 2)->default(0);
            $table->decimal('final_price_child', 10, 2)->default(0);
            $table->decimal('final_price_guide', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->cascadeOnDelete();
            $table->foreign('season_id')->references('id')->on('seasons')->cascadeOnDelete();
            $table->foreign('wholesaler_rate_id')->references('id')->on('wholesaler_rates')->nullOnDelete();
            $table->foreign('discount_id')->references('id')->on('discounts')->nullOnDelete();
            $table->foreign('promotion_id')->references('id')->on('promotions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pricing');
    }
};
