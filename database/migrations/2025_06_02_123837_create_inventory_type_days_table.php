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
        Schema::create('inventory_type_days', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('inventory_id')->nullable()->constrained('inventories');
            $table->foreignId('type_day_id')->nullable()->constrained('type_days')->onDelete('cascade');
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
        Schema::dropIfExists('inventory_type_day_pivot');
    }
};
