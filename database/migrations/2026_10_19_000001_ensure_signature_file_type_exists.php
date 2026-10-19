<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $signatureType = DB::table('type_files')
            ->where('name', 'signature')
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->first();

        if ($signatureType) {
            $signatureTypeId = $signatureType->id;
            DB::table('type_files')->where('id', $signatureTypeId)->update([
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
        } else {
            $signatureTypeId = DB::table('type_files')->insertGetId([
                'name' => 'signature',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('files')
            ->whereNull('type_file_id')
            ->where('table_name', 'customers')
            ->where('file_type', 'image/png')
            ->where('path', 'like', 'signatures/%')
            ->update([
                'type_file_id' => $signatureTypeId,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // Keep shared lookup data and repaired file classifications intact.
    }
};
