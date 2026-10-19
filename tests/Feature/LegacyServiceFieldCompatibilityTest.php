<?php

use App\Models\ServiceField;
use App\Models\ServiceFieldValue;
use Illuminate\Support\Facades\DB;

it('allows the legacy frontend to resolve soft-deleted service field definitions', function () {
    $fieldId = DB::table('service_fields')->insertGetId([
        'name' => 'Legacy: Activity description',
        'slug' => 'legacy-test-activity-description',
        'order' => 99,
        'required' => false,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => now(),
    ]);

    $value = new ServiceFieldValue(['field_id' => $fieldId]);
    $value->field_id = $fieldId;

    expect($value->field)->toBeNull()
        ->and($value->fieldIncludingTrashed)->toBeInstanceOf(ServiceField::class)
        ->and($value->fieldIncludingTrashed->slug)->toBe('legacy-test-activity-description');
});
