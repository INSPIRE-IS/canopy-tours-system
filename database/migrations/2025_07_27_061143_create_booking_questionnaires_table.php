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
            $table->foreignId('guide_id')->constrained('users');
            $table->foreignId('safety_guide_id')->constrained('users');
            $table->foreignId('type_group_id');
            $table->string('signature');
            $table->date('birth_date');
            $table->string('type_referral');
            $table->integer('country_id');
            $table->integer('province_id');
            $table->boolean('is_medical');
            $table->string('medical_detail');
            $table->integer('recieve_invite');
            $table->string('pully_number');
            $table->string('helmet_size');
            $table->integer('gender_id');
            $table->boolean('is_fit');
            $table->boolean('is_minor');
            $table->boolean('is_agree');
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
