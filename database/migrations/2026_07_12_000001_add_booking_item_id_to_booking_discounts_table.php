<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_discounts', function (Blueprint $table) {
            $table->foreignId('booking_item_id')
                ->nullable()
                ->after('booking_id')
                ->constrained('booking_items')
                ->nullOnDelete();

            $table->index(['booking_id', 'booking_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('booking_discounts', function (Blueprint $table) {
            $table->dropIndex(['booking_id', 'booking_item_id']);
            $table->dropConstrainedForeignId('booking_item_id');
        });
    }
};
