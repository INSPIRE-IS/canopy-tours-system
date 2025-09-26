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
        Schema::create('booking_inventories', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('adults')->default(0)->nullable();
            $table->integer('children')->default(0)->nullable();
            $table->integer('guides')->default(0)->nullable();
            $table->integer('pax')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_item_id')->constrained('booking_items');
            $table->foreignId('booking_detail_id')->constrained('booking_details');
            $table->foreignId('inventory_type_day_id')->nullable()->constrained('inventory_type_days');
            $table->foreignId('inventory_time_slot_id')->nullable()->constrained('inventory_time_slots');
            $table->foreignId('inventory_override_id')->nullable()->constrained('inventory_overrides');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_inventories');
    }
};
