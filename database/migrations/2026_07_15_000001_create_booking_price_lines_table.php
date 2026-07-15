<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_price_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('booking_item_id')->constrained('booking_items')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams');

            $table->date('activity_date')->nullable();
            $table->time('activity_time')->nullable();

            $table->string('source_type', 40)->default('rack');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('pax_type', 20);
            $table->unsignedInteger('count')->default(0);

            $table->decimal('base_rate', 10, 2)->default(0);
            $table->decimal('final_rate', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2)->default(0);

            $table->string('label')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('booking_id');
            $table->index('booking_item_id');
            $table->index(['team_id', 'activity_date']);
            $table->index(['team_id', 'activity_date', 'source_type']);
            $table->index(['team_id', 'source_type']);
            $table->index(['team_id', 'source_type', 'source_id']);
            $table->index(['team_id', 'pax_type']);
            $table->index(['team_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_price_lines');
    }
};
