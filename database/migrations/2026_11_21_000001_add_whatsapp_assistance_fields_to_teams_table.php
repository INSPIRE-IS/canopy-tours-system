<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('whatsapp_enabled')->default(false)->after('website_url');
            $table->string('whatsapp_number', 13)->nullable()->after('whatsapp_enabled');
            $table->string('whatsapp_message', 500)
                ->default('Hi, I need assistance with an online booking.')
                ->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_enabled',
                'whatsapp_number',
                'whatsapp_message',
            ]);
        });
    }
};
