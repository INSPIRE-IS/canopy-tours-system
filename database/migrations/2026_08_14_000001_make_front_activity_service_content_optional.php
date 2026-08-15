<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $contentSlugs = [
        'description',
        'condition_text',
        'activity_description',
        'arrangements',
        'requirements',
        'inclusions',
        'exclusions',
        'notes',
        'lat_lon_start',
        'lat_long_end',
    ];

    public function up(): void
    {
        DB::statement('ALTER TABLE service_field_values MODIFY value LONGTEXT NULL');

        DB::table('service_fields')
            ->whereIn('slug', $this->contentSlugs)
            ->update(['required' => false]);

        DB::table('service_field_values')
            ->whereIn('field_id', function ($query) {
                $query->select('id')
                    ->from('service_fields')
                    ->whereIn('slug', $this->contentSlugs);
            })
            ->where(function ($query) {
                $query->whereNull('value')
                    ->orWhereRaw("TRIM(REPLACE(REPLACE(REPLACE(value, CHAR(13), ''), CHAR(10), ''), '&nbsp;', ' ')) IN ('', '.', '<p>.</p>', '<p><br></p>', '<p>&nbsp;</p>')");
            })
            ->update(['value' => null]);
    }

    public function down(): void
    {
        DB::table('service_field_values')
            ->whereNull('value')
            ->update(['value' => '']);

        DB::statement('ALTER TABLE service_field_values MODIFY value LONGTEXT NOT NULL');
    }
};
