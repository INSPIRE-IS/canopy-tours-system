<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wholesaler_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_adult', 10, 2);
            $table->decimal('price_child', 10, 2)->nullable();
            $table->decimal('price_guide', 10, 2)->nullable();
            $table->timestamps();
            $table->foreignId('wholesaler_id')->constrained('wholesalers')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->unique(['wholesaler_id', 'service_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesaler_rates');
    }
};
