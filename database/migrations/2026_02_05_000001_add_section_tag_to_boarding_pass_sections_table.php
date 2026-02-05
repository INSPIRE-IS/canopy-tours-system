<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boarding_pass_sections', function (Blueprint $table) {
            $table->string('section_tag')->nullable()->after('boarding_pass_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('boarding_pass_sections', function (Blueprint $table) {
            $table->dropColumn('section_tag');
        });
    }
};
