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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->string('sender');
            $table->string('receiver');
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('booking_id')->nullable()->constrained('bookings');
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
