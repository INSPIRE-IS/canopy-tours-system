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
        Schema::create('booking_pax_discounts', function (Blueprint $table) {
            $table->id();
            $table->enum('pax_type', ['adults', 'children', 'guides'])->default('adults');
            $table->integer('count')->default(0);
            $table->decimal('base_rate', 10, 2); // 0-100
            $table->decimal('final_rate', 10, 2); // 0-100
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_pricing_id')->constrained('booking_pricing')->onDelete('cascade');
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');
            $table->index(['booking_pricing_id', 'discount_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_pax_discounts');
    }
};
