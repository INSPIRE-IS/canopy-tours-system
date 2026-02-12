<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_fees', function (Blueprint $table) {
            $table->longText('email_reminder')->nullable()->after('invoice_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_fees', function (Blueprint $table) {
            $table->dropColumn('email_reminder');
        });
    }
};
