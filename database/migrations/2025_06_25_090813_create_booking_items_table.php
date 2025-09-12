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
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            // Pax counts
            $table->unsignedInteger('pax_adults')->default(0);
            $table->unsignedInteger('pax_children')->default(0);
            $table->unsignedInteger('pax_guides')->default(0);

            // Pricing snapshot
            $table->decimal('price_adult', 10, 2)->default(0);
            $table->decimal('price_child', 10, 2)->default(0);
            $table->decimal('price_guide', 10, 2)->default(0);

            // Relationships to existing entities
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('season_id')->constrained('seasons');
            $table->foreignId('inventory_id')->constrained('inventories');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
