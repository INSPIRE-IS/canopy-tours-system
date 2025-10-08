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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('table_row_id');
            $table->string('file_name');
            $table->string('file_type');
            $table->string('path');
            $table->string('expiry')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->foreignId('type_file_id')->nullable()->constrained('type_files');
            $table->foreignId('booking_id')->nullable()->constrained('bookings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
