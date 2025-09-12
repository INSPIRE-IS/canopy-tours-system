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
        Schema::create('booking_items_customer', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_lead')->default(0)->nullable();
            $table->timestamps();

            $table->foreignId('booking_item_id')->constrained('booking_items');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_items_customer_pivot');
    }
};
