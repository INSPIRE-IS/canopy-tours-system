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
        Schema::table('type_help_questions', function (Blueprint $table) {
            $table->json('role_ids')->nullable()->after('name');
            $table->integer('order_id')->nullable()->after('role_ids')->index();
        });
        Schema::table('help_answers', function (Blueprint $table) {
            $table->mediumText('answer')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_help_questions', function (Blueprint $table) {
            $table->dropColumn(['role_ids', 'order_id']);
        });
        // Schema::table('help_answers', function (Blueprint $table) {
        //     $table->string('answer')->nullable()->change();
        // });
    }
};
