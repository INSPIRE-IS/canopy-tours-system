<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('closeouts', function (Blueprint $table) {
            $table->unsignedSmallInteger('priority')->default(50)->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('closeouts', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
