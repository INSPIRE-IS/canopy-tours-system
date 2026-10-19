<?php

use Illuminate\Support\Facades\DB;

it('restores the signature file type and backfills recognizable signature files', function () {
    DB::table('type_files')->where('name', 'signature')->delete();

    $fileId = DB::table('files')->insertGetId([
        'table_name' => 'customers',
        'table_row_id' => '123',
        'file_name' => 'signature_test.png',
        'file_type' => 'image/png',
        'path' => 'signatures/signature_test.png',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_10_19_000001_ensure_signature_file_type_exists.php');
    $migration->up();
    $migration->up();

    $signatureTypes = DB::table('type_files')->where('name', 'signature')->get();

    expect($signatureTypes)->toHaveCount(1)
        ->and($signatureTypes->first()->deleted_at)->toBeNull()
        ->and(DB::table('files')->where('id', $fileId)->value('type_file_id'))
        ->toBe($signatureTypes->first()->id);
});
