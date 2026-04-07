<?php

use App\Models\Lookup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lookup_items', function (Blueprint $table) {
            $table->boolean('value')->nullable()->after('multiplier');
        });

        $lookupId = Lookup::where('slug', 'settings')->value('id');
        if ($lookupId) {
            DB::table('lookup_items')
                ->where('lookup_id', $lookupId)
                ->update([
                    'value' => DB::raw('IF(multiplier IS NULL, 0, IF(multiplier = 0, 0, 1))'),
                    'multiplier' => null,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('lookup_items', function (Blueprint $table) {
            $table->dropColumn('value');
        });
    }
};
