<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wholesaler_rates')
            ->leftJoin('wholesalers', 'wholesaler_rates.wholesaler_id', '=', 'wholesalers.id')
            ->whereNull('wholesaler_rates.deleted_at')
            ->where(function ($query) {
                $query->whereNull('wholesalers.id')
                    ->orWhereNotNull('wholesalers.deleted_at');
            })
            ->update([
                'wholesaler_rates.deleted_at' => now(),
                'wholesaler_rates.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally not restoring rows. These rates belonged to deleted/missing wholesalers,
        // and restoring them could make inactive supplier rates selectable again.
    }
};
