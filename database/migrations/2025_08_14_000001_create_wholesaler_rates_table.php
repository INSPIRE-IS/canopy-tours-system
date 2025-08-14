<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wholesaler_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wholesaler_id');
            $table->unsignedBigInteger('season_id');
            $table->decimal('price_adult', 10, 2);
            $table->decimal('price_child', 10, 2)->nullable();
            $table->decimal('price_guide', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('wholesaler_id')->references('id')->on('wholesalers')->cascadeOnDelete();
            $table->foreign('season_id')->references('id')->on('seasons')->cascadeOnDelete();
            $table->unique(['wholesaler_id','season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesaler_rates');
    }
};
