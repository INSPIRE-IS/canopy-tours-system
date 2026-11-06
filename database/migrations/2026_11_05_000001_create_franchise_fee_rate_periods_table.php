<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('franchise_fee_rate_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams');
            $table->decimal('royalty_percent', 5, 2);
            $table->decimal('marketing_percent', 5, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['team_id', 'is_active', 'effective_from', 'effective_to'],
                'franchise_fee_rate_periods_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('franchise_fee_rate_periods');
    }
};
