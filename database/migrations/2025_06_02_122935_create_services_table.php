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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->longText('description')->nullable();
            $table->string('pillbox_text');
            $table->longText('activity_description')->nullable();
            $table->longText('arrangements');
            $table->longText('requirements')->nullable();
            $table->longText('inclusions_exclusions');
            $table->longText('notes');
            $table->string('lat_lon_start');
            $table->string('lat_long_end');
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('created_by_id')->nullable()->constrained('users');
            $table->foreignId('updated_by_id')->nullable()->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained('teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
