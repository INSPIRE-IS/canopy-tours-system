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
        Schema::create('service_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->integer('order')->default(0)->nullable();
            $table->boolean('required')->default(0)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('type_category_id')->nullable()->constrained('type_categories');
            $table->foreignId('type_input_id')->nullable()->constrained('type_inputs');
            $table->foreignId('type_data_id')->nullable()->constrained('type_datas');
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
        Schema::dropIfExists('season_fields');
    }
};
