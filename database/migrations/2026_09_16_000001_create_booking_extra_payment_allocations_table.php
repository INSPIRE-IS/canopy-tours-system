<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_extra_payment_allocations')) {
            return;
        }

        Schema::create('booking_extra_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_extra_id')->constrained('booking_extras')->cascadeOnDelete();
            $table->foreignId('booking_payment_id')->nullable()->constrained('booking_payments')->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('allocation_type', 30)->default('payment');
            $table->text('note')->nullable();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['booking_id', 'allocation_type']);
            $table->index(['booking_extra_id', 'allocation_type']);
            $table->index(['booking_payment_id', 'allocation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extra_payment_allocations');
    }
};
