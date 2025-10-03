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
        Schema::create('booking_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_item_id')->constrained('booking_items');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('guide_id')->constrained('users')->nullable();
            $table->foreignId('safety_guide_id')->constrained('users')->nullable();
            $table->foreignId('type_group_id')->nullable();
            $table->string('signature')->nullable();
            $table->integer('age')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('type_referral')->nullable();
            $table->integer('country_id')->nullable();
            $table->integer('province_id')->nullable();
            $table->boolean('is_medical')->nullable();
            $table->string('medical_detail')->nullable();
            $table->integer('recieve_invite')->nullable();
            $table->string('pully_number')->nullable();
            $table->string('helmet_size')->nullable();
            $table->integer('gender_id')->nullable();
            $table->boolean('is_fit')->nullable();
            $table->boolean('is_minor')->nullable();
            $table->boolean('is_agree')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_questionnaires');
    }
};
