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
        if (Schema::hasTable('booking_payments')) {
            return;
        }

        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->date('payment_date');
            $table->float('ex_vat_amount', 8, 2);
            $table->foreignId('lookup_item_id')->constrained('lookup_items');
            $table->float('vat', 8, 2);
            $table->float('vat_amount', 8, 2);
            $table->string('voucher_code')->nullable();
            $table->string('redeemer_name')->nullable();
            $table->longText('note')->nullable();
            $table->string('reference');
            $table->longText('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();


            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('type_pay_option_id')->constrained('type_pay_options');
            $table->foreignId('type_payment_id')->nullable()->constrained('type_payments');
            $table->foreignId('gateway_id')->nullable()->constrained('gateways');
            $table->foreignId('status_payment_id')->nullable()->constrained('status_payments');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
