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
        Schema::create('questionnaire_fields', function (Blueprint $table) {
            $table->id();

            $table->string('question');
            $table->string('slug');
            $table->json('options')->nullable();
            // $table->enum('type', ['text', 'long_text', 'number', 'date', 'time', 'checkbox']);
            $table->boolean('required')->default(false);
            $table->boolean('hidden')->default(false);
            $table->integer('sort_order')->default(0); // if you want multi-step forms            
            //$table->json('conditional_logic')->nullable(); //  e.g. show this only if Age < 18
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('type_input_id')->nullable()->constrained('type_inputs');
            $table->foreignId('type_data_id')->nullable()->constrained('type_datas');
            $table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_fields');
    }
};
