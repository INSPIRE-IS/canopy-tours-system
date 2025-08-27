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
            $table->enum('type', ['percent', 'fixed_value', 'graduated', 'varied_child'])->default('percent');
            $table->decimal('value', 10, 2)->default(0); // meaning depends on type
            $table->date('booking_start_date'); // so basically like black friday weekend we pick a start friday
            $table->date('booking_end_date'); // so basically like black friday weekend we pick a end monday
            $table->date('activity_start_date'); // we say which period of the activity we want to apply this promotion to start
            $table->date('activity_end_date');  // we say which period of the activity we want to apply this promotion to end
            //$table->string('valid_days')->nullable(); // comma-separated: mon,tue,wed,thu,fri,sat,sun
            $table->boolean('internal_use_only')->default(false);
            $table->timestamps();

            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->index(['service_id', 'booking_start_date', 'booking_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
