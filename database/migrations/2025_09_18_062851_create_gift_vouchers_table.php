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
        Schema::create('gift_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('email_address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('voucher_code')->nullable();
            $table->float('grand_tot_due', 8, 2);
            $table->longText('note')->nullable();
            $table->date('expire_date')->nullable();
            $table->foreignId('status_payment_id')->nullable()->constrained('status_payments');
            $table->date('paid_date')->nullable();
            $table->boolean('redeemed')->default(false);
            $table->date('redeemed_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_id')->nullable()->constrained('bookings');
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->foreignId('season_id')->nullable()->constrained('seasons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_vouchers');
    }
};
