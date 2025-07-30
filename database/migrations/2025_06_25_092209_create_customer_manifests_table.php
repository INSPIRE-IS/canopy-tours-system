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
        Schema::create('customer_manifests', function (Blueprint $table) {
            $table->id();
            $table->string('position')->nullable();
            $table->longText('notes')->nullable();
            $table->boolean('is_indemnity')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_manifests');
    }
};
