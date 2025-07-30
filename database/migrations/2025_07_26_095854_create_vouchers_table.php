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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_code')->nullable();
            $table->float('amount', 8, 2);
            $table->longText('note')->nullable();
            $table->date('expire_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->foreignId('season_id')->nullable()->constrained('seasons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
