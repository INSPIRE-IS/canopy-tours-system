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
        Schema::create('promotion_day_time_slots', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('promotion_id')->constrained('promotions');
            $table->foreignId('day_id')->constrained('type_days'); // 1=Mon .. 7=Sun
            $table->foreignId('time_slot_id')->nullable()->constrained('inventory_time_slots');
            //$table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_day_time_slots');
    }
};
