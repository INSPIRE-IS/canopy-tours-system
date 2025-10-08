<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wholesaler_rates', function (Blueprint $table) {
            $table->id();
            $table->float('price_adult', 8, 2);
            $table->float('price_child', 8, 2);
            $table->float('price_guide', 8, 2)->default(0.00);
            $table->boolean('bill_back')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('wholesaler_id')->constrained('wholesalers')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->foreignId('team_id')->nullable();
            $table->unique(['wholesaler_id', 'service_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesaler_rates');
    }
};
