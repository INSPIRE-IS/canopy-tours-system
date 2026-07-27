<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesaler_rates', function (Blueprint $table) {
            $table->boolean('is_prepaid_voucher')->default(false)->after('bill_back');
        });
    }

    public function down(): void
    {
        Schema::table('wholesaler_rates', function (Blueprint $table) {
            $table->dropColumn('is_prepaid_voucher');
        });
    }
};
