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
        Schema::create('booking_fees', function (Blueprint $table) {
            $table->id();
            $table->float('tot_adults', 8, 2)->nullable();
            $table->float('tot_children', 8, 2)->nullable();
            $table->float('tot_guides', 8, 2)->nullable();
            $table->boolean('is_voucher')->default(0)->nullable();
            $table->string('voucher_code')->nullable();
            $table->longText('invoice_note')->nullable();
            $table->boolean('is_email_reminder')->default(0)->nullable();
            $table->longText('booking_note')->nullable();
            $table->float('tot_amount', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_item_id')->constrained('booking_items');
            $table->foreignId('booking_detail_id')->nullable()->constrained('booking_details');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_fees');
    }
};
