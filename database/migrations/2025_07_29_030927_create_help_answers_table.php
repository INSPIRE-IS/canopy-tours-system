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
        Schema::create('help_answers', function (Blueprint $table) {
            $table->id();
            $table->string('answer');
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('help_question_id')->constrained('help_questions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_answers');
    }
};
