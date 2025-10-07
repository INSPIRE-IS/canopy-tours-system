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
        Schema::create('booking_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_voucher_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('helper')->nullable();
            $table->text('description')->nullable();
            $table->boolean('disabled')->default(false);
            $table->string('type')->nullable();
            $table->string('format')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_voucher_items');
    }
};
