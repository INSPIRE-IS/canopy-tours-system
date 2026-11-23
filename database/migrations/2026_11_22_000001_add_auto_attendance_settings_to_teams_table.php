<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('auto_attendance_enabled')
                ->default(false)
                ->after('whatsapp_message');
            $table->string('auto_attendance_timing', 20)
                ->default('same_day')
                ->after('auto_attendance_enabled');
            $table->time('auto_attendance_run_time')
                ->default('20:00:00')
                ->after('auto_attendance_timing');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'auto_attendance_enabled',
                'auto_attendance_timing',
                'auto_attendance_run_time',
            ]);
        });
    }
};
