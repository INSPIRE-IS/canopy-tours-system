<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_fees', function (Blueprint $table) {
            $table->boolean('is_prepaid_wholesaler_voucher')->default(false)->after('voucher_code');
        });
    }

    public function down(): void
    {
        Schema::table('booking_fees', function (Blueprint $table) {
            $table->dropColumn('is_prepaid_wholesaler_voucher');
        });
    }
};
