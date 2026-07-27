<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->foreignId('wholesaler_rate_id')
                ->nullable()
                ->after('wholesaler_id')
                ->constrained('wholesaler_rates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wholesaler_rate_id');
        });
    }
};
