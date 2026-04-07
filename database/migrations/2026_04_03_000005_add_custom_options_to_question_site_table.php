<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('question_site', function (Blueprint $table) {
            $table->json('custom_options')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('question_site', function (Blueprint $table) {
            $table->dropColumn('custom_options');
        });
    }
};
