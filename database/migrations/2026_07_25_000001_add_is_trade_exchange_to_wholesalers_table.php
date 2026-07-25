<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesalers', function (Blueprint $table) {
            $table->boolean('is_trade_exchange')->default(false)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('wholesalers', function (Blueprint $table) {
            $table->dropColumn('is_trade_exchange');
        });
    }
};
