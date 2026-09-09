<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $slug = 'force_adult_guardian_selection';

    public function up(): void
    {
        $now = now();
        $lookupId = DB::table('lookups')->where('slug', 'settings')->value('id');

        if (!$lookupId) {
            $lookupId = DB::table('lookups')->insertGetId([
                'name' => 'Settings',
                'slug' => 'settings',
                'team_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!$lookupId) {
            return;
        }

        $setting = [
            'slug' => $this->slug,
            'name' => 'Force Adult Guardian Selection',
            'info' => 'Require minor/child indemnities to choose a parent/guardian from the adults on the booking. If disabled, a free-text parent/guardian name is captured.',
            'value' => 0,
        ];

        $this->upsertSetting($lookupId, null, $setting, $now);

        foreach (DB::table('teams')->pluck('id') as $teamId) {
            $this->upsertSetting($lookupId, (int) $teamId, $setting, $now);
        }
    }

    public function down(): void
    {
        DB::table('lookup_items')
            ->where('slug', $this->slug)
            ->delete();
    }

    private function upsertSetting(int $lookupId, ?int $teamId, array $setting, $now): void
    {
        $query = DB::table('lookup_items')
            ->where('lookup_id', $lookupId)
            ->where('slug', $setting['slug']);

        $teamId === null
            ? $query->whereNull('team_id')
            : $query->where('team_id', $teamId);

        $existingId = $query->value('id');

        if ($existingId) {
            DB::table('lookup_items')
                ->where('id', $existingId)
                ->update([
                    'name' => $setting['name'],
                    'info' => $setting['info'],
                    'value' => (int) $setting['value'],
                    'multiplier' => null,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('lookup_items')->insert([
            'name' => $setting['name'],
            'info' => $setting['info'],
            'slug' => $setting['slug'],
            'rate' => null,
            'multiplier' => null,
            'value' => (int) $setting['value'],
            'effective_from' => $now->toDateString(),
            'effective_to' => null,
            'is_default' => 1,
            'is_active' => 1,
            'team_id' => $teamId,
            'lookup_id' => $lookupId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
