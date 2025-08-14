<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('promo_code')->unique();
            $table->enum('type', ['percent','fixed_value','graduated','varied_child'])->default('percent');
            $table->decimal('value', 10, 2)->default(0); // meaning depends on type
            $table->unsignedBigInteger('service_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('valid_days')->nullable(); // comma-separated: mon,tue,wed,thu,fri,sat,sun
            $table->boolean('internal_use_only')->default(false);
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->index(['service_id','start_date','end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
