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
        Schema::create('help_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('type_help_question_id')->constrained('type_help_questions')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_questions');
    }
};
