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
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('m_payment_id')->nullable();
            $table->string('pf_payment_id')->nullable();
            $table->decimal('amount_gross', 10, 2)->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('ip_address')->nullable();
            $table->longText('payload')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('gateway_id')->nullable()->constrained('gateways')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('gift_voucher_id')->nullable()->constrained('gift_vouchers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
