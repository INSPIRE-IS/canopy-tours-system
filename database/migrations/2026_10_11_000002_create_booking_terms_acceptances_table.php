<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamp('accepted_at');
            $table->text('wording_snapshot');
            $table->char('wording_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'accepted_at'], 'booking_terms_team_accepted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_terms_acceptances');
    }
};
