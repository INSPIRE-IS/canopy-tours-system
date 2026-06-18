<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_payments', 'short_payment_note')) {
                $table->longText('short_payment_note')->nullable()->after('additional_amount');
            }

            if (!Schema::hasColumn('booking_payments', 'additional_payment_note')) {
                $table->longText('additional_payment_note')->nullable()->after('short_payment_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_payments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_payments', 'additional_payment_note')) {
                $table->dropColumn('additional_payment_note');
            }

            if (Schema::hasColumn('booking_payments', 'short_payment_note')) {
                $table->dropColumn('short_payment_note');
            }
        });
    }
};
