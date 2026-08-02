<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('type_alerts')->updateOrInsert(
            ['id' => 11],
            [
                'name' => 'Pregnancy',
                'slug' => 'pregnancy',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('type_alerts')->where('id', 11)->where('slug', 'pregnancy')->delete();
    }
};
