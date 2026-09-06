<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_indemnity_documents', function (Blueprint $table) {
            $table->string('signing_place')->nullable()->after('guardian_name');
        });
    }

    public function down(): void
    {
        Schema::table('customer_indemnity_documents', function (Blueprint $table) {
            $table->dropColumn('signing_place');
        });
    }
};
