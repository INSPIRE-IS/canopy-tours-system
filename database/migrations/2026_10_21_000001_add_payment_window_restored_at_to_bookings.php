<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'payment_window_restored_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->timestamp('payment_window_restored_at')
                    ->nullable()
                    ->after('payment_window_expired_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'payment_window_restored_at')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('payment_window_restored_at');
            });
        }
    }
};
