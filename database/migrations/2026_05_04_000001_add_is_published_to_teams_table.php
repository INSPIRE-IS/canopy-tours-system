<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teams')) {
            return;
        }

        if (!Schema::hasColumn('teams', 'is_published')) {
            Schema::table('teams', function (Blueprint $table) {
                // Default to published so existing sites keep current frontend behavior.
                $table->boolean('is_published')->default(true)->after('branch_code');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('teams')) {
            return;
        }

        if (Schema::hasColumn('teams', 'is_published')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropColumn('is_published');
            });
        }
    }
};

