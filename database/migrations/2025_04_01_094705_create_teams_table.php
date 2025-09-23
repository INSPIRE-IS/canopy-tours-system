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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('image')->nullable();
            $table->string('color')->nullable();
            $table->string('reg_no')->nullable();
            $table->string('address')->nullable();
            $table->string('address_code')->nullable();
            $table->string('account_owner')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch_code')->nullable();
            $table->timestamps();
            $table->softDeletes();

            //$table->foreignId('owner_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
