<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE promotions MODIFY type ENUM('percent','fixed_value','graduated','varied_child','volume_discount','pax_exact_discount','time_slot') NOT NULL DEFAULT 'percent'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE promotions MODIFY type ENUM('percent','fixed_value','graduated','varied_child','volume_discount','time_slot') NOT NULL DEFAULT 'percent'");
    }
};
