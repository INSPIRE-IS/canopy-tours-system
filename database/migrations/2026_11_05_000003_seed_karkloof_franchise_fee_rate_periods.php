<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TEAM_ID = 3;

    public function up(): void
    {
        if (!DB::table('teams')->where('id', self::TEAM_ID)->whereNull('deleted_at')->exists()) {
            return;
        }

        $now = now();
        $periods = [
            [
                'effective_from' => '2020-01-01',
                'effective_to' => '2026-10-31',
                'royalty_percent' => 10.00,
                'marketing_percent' => 1.50,
                'note' => 'Initial historical franchise fee period.',
            ],
            [
                'effective_from' => '2026-11-01',
                'effective_to' => null,
                'royalty_percent' => 9.00,
                'marketing_percent' => 1.50,
                'note' => 'Royalty reduced to 9% from 1 November 2026.',
            ],
        ];

        foreach ($periods as $period) {
            DB::table('franchise_fee_rate_periods')->updateOrInsert(
                [
                    'team_id' => self::TEAM_ID,
                    'effective_from' => $period['effective_from'],
                ],
                [
                    ...$period,
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('franchise_fee_rate_periods')
            ->where('team_id', self::TEAM_ID)
            ->whereIn('effective_from', ['2020-01-01', '2026-11-01'])
            ->delete();
    }
};
