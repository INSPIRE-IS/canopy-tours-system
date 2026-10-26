<?php

use App\Http\Controllers\Admin\BookingsController;
use App\Models\File;
use Illuminate\Support\Facades\Storage;

it('resolves a stored guest email attachment from its raw relative path', function () {
    Storage::fake('public');
    Storage::disk('public')->put('invoices/INV-2026-000852.pdf', 'pdf contents');

    $file = new File(['path' => 'invoices/INV-2026-000852.pdf']);
    $file->syncOriginal();
    $method = new ReflectionMethod(BookingsController::class, 'publicStoragePath');

    $resolvedPath = $method->invoke(new BookingsController(), $file);

    expect($file->path)->toContain('/storage/invoices/INV-2026-000852.pdf')
        ->and($resolvedPath)->toBe(Storage::disk('public')->path('invoices/INV-2026-000852.pdf'));
});

it('does not resolve a guest email attachment whose stored file is missing', function () {
    Storage::fake('public');

    $file = new File(['path' => 'invoices/missing.pdf']);
    $file->syncOriginal();
    $method = new ReflectionMethod(BookingsController::class, 'publicStoragePath');

    expect($method->invoke(new BookingsController(), $file))->toBeNull();
});
