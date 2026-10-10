<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $table->boolean('include_public_extras')->default(false)->after('internal_only');
        });

        Schema::create('season_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('name', 30);
            $table->string('description', 255)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->boolean('is_per_guest')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id', 'is_active', 'sort_order'], 'season_extras_public_idx');
            $table->index(['team_id', 'season_id'], 'season_extras_team_idx');
        });

        Schema::table('booking_extras', function (Blueprint $table) {
            $table->foreignId('season_extra_id')
                ->nullable()
                ->after('service_id')
                ->constrained('season_extras')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_extras', function (Blueprint $table) {
            $table->dropConstrainedForeignId('season_extra_id');
        });

        Schema::dropIfExists('season_extras');

        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn('include_public_extras');
        });
    }
};
