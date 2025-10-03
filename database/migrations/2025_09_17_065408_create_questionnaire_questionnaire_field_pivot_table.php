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
        Schema::create('questionnaire_questionnaire_field', function (Blueprint $table) {
            $table->id();
            $table->integer('sort_order')->default(0); // if you want multi-step forms
            $table->boolean('required')->default(false);
            $table->boolean('visable')->default(false);
            $table->timestamps();

            $table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
            $table->foreignId('questionnaire_field_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_questionnaire_field_pivot');
    }
};
