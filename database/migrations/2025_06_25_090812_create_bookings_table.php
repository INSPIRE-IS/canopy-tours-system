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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('lead_name');
            $table->boolean('is_sms')->default(0)->nullable();
            $table->boolean('is_corporate')->default(0)->nullable();
            $table->date('due_date')->nullable();
            $table->float('tot_paid', 8, 2);
            $table->float('grand_tot_due', 8, 2);
            $table->string('reference');
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('status_booking_id')->nullable()->constrained('status_bookings');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
