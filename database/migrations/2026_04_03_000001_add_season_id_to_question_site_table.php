<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_site', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->after('service_id')
                ->constrained('seasons')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_site', function (Blueprint $table) {
            $table->dropConstrainedForeignId('season_id');
        });
    }
};
