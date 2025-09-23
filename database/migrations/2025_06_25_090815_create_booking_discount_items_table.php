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
        Schema::create('booking_discount_items', function (Blueprint $table) {
            $table->id();
            $table->enum('pax_type', ['adult', 'child', 'guide'])->default('adult');
            $table->integer('count')->default(0);
            $table->decimal('base_rate', 10, 2); // 0-100
            $table->decimal('final_rate', 10, 2); // 0-100
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_discount_id')->constrained('booking_discounts')->onDelete('cascade');
            $table->foreignId('discount_id')->nullable()->constrained('discounts')->onDelete('cascade');
            $table->index(['booking_discount_id', 'discount_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_discount_items');
    }
};
