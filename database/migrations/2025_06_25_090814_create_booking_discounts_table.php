<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_discounts', function (Blueprint $table) {
            $table->id();
            $table->decimal('final_price_adult', 10, 2)->default(0);
            $table->decimal('final_price_child', 10, 2)->default(0);
            $table->decimal('final_price_guide', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_discounts');
    }
};
