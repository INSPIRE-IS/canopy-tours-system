<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $fields = [
        ['id' => 1, 'slug' => 'description', 'name' => 'Frontend: Hero summary', 'order' => 1, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => false, 'type_category_id' => 1],
        ['id' => 11, 'slug' => 'minimum_age', 'name' => 'Frontend: Minimum age (years)', 'order' => 2, 'type_data' => 'integer', 'type_input' => 'number', 'deleted_at' => false, 'type_category_id' => 1],
        ['id' => 5, 'slug' => 'requirements', 'name' => 'Frontend: Before you go', 'order' => 3, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => false, 'type_category_id' => 1],
        ['id' => 6, 'slug' => 'inclusions', 'name' => "Frontend: What's included", 'order' => 4, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => false, 'type_category_id' => 1],
        ['id' => 2, 'slug' => 'condition_text', 'name' => 'Frontend: Cancellation policy', 'order' => 5, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => false, 'type_category_id' => 1],
        ['id' => 3, 'slug' => 'activity_description', 'name' => 'Legacy: Activity description', 'order' => 6, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => true, 'type_category_id' => 1],
        ['id' => 4, 'slug' => 'arrangements', 'name' => 'Legacy fallback: Arrangements', 'order' => 7, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => true, 'type_category_id' => 1],
        ['id' => 7, 'slug' => 'exclusions', 'name' => 'Legacy: Exclusions', 'order' => 8, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => true, 'type_category_id' => 1],
        ['id' => 8, 'slug' => 'notes', 'name' => 'Legacy: Notes', 'order' => 9, 'type_data' => 'text', 'type_input' => 'textarea', 'deleted_at' => true, 'type_category_id' => 1],
        ['id' => 9, 'slug' => 'lat_lon_start', 'name' => 'Reserved: Start coordinates', 'order' => 10, 'type_data' => 'string', 'type_input' => 'text', 'deleted_at' => false, 'type_category_id' => 1],
        ['id' => 10, 'slug' => 'lat_long_end', 'name' => 'Reserved: End coordinates', 'order' => 11, 'type_data' => 'string', 'type_input' => 'text', 'deleted_at' => false, 'type_category_id' => 1]
    ];

    public function up(): void
    {
        $now = now();
        $categoryId = DB::table('type_categories')->where('name', 'Details')->value('id')
            ?? DB::table('type_categories')->orderBy('id')->value('id');

        foreach ($this->fields as $definition) {
            $existing = DB::table('service_fields')->where('slug', $definition['slug'])->first();
            $attributes = [
                'name' => $definition['name'],
                'order' => $definition['order'],
                'required' => false,
                'updated_at' => $now,
                'deleted_at' => $definition['deleted_at'] ? $now : null,
                'type_category_id' => $categoryId,
                'type_data_id' => DB::table('type_datas')->where('name', $definition['type_data'])->value('id'),
                'type_input_id' => DB::table('type_inputs')->where('name', $definition['type_input'])->value('id')
            ];

            if ($existing) {
                DB::table('service_fields')->where('slug', $definition['slug'])->update($attributes);

                continue;
            }

            $preferredIdAvailable = ! DB::table('service_fields')->where('id', $definition['id'])->exists();
            $typeDataId = DB::table('type_datas')->where('name', $definition['type_data'])->value('id');
            $typeInputId = DB::table('type_inputs')->where('name', $definition['type_input'])->value('id');

            DB::table('service_fields')->insert(array_filter([
                'id' => $preferredIdAvailable ? $definition['id'] : null,
                'name' => $definition['name'],
                'slug' => $definition['slug'],
                'order' => $definition['order'],
                'required' => false,
                'type_category_id' => $categoryId,
                'type_data_id' => $typeDataId,
                'type_input_id' => $typeInputId,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => $definition['deleted_at'] ? $now : null,
            ], static fn($value) => $value !== null));
        }
    }

    public function down(): void
    {
        $legacyNames = [
            'description' => ['Description', 1],
            'condition_text' => ['Condition Text', 2],
            'activity_description' => ['Activity Description', 3],
            'arrangements' => ['Arrangements', 4],
            'requirements' => ['Requirements', 5],
            'inclusions' => ['Inclusions', 6],
            'exclusions' => ['Exclusions', 7],
            'notes' => ['Notes', 8],
            'lat_lon_start' => ['lat-lon start', 9],
            'lat_long_end' => ['lat-long end', 10],
        ];

        foreach ($legacyNames as $slug => [$name, $order]) {
            DB::table('service_fields')->where('slug', $slug)->update([
                'name' => $name,
                'order' => $order,
                'updated_at' => now(),
            ]);
        }

        $minimumAgeId = DB::table('service_fields')->where('slug', 'minimum_age')->value('id');
        if ($minimumAgeId && ! DB::table('service_field_values')->where('field_id', $minimumAgeId)->exists()) {
            DB::table('service_fields')->where('id', $minimumAgeId)->delete();
        }
    }
};
