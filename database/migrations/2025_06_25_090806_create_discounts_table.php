<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('applies_to', ['line_item', 'invoice'])->default('line_item');
            $table->decimal('percent_off', 5, 2); // 0-100
            $table->timestamps();

            // $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            // $table->index(['service_id', 'applies_to']);
            $table->foreignId('team_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
