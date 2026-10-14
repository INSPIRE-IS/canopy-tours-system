<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('payment_window_expired_at')->nullable()->after('status_payment_id');
            $table->index(
                ['payment_window_expired_at', 'status_payment_id'],
                'bookings_pay_window_exp_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_pay_window_exp_idx');
            $table->dropColumn('payment_window_expired_at');
        });
    }
};
