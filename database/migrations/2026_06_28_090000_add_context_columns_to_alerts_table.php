<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->after('team_id')->constrained('bookings')->nullOnDelete();
            $table->foreignId('booking_item_id')->nullable()->after('booking_id')->constrained('booking_items')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->after('booking_item_id')->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_manifest_id')->nullable()->after('customer_id')->constrained('customer_manifests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_manifest_id');
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('booking_item_id');
            $table->dropConstrainedForeignId('booking_id');
        });
    }
};
