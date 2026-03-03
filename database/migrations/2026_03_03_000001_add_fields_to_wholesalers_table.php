<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wholesalers', function (Blueprint $table) {
            $table->string('reg_no')->nullable()->after('name');
            $table->string('vat_no')->nullable()->after('reg_no');
            $table->string('address')->nullable()->after('vat_no');
        });
    }

    public function down(): void
    {
        Schema::table('wholesalers', function (Blueprint $table) {
            $table->dropColumn(['reg_no', 'vat_no', 'address']);
        });
    }
};
