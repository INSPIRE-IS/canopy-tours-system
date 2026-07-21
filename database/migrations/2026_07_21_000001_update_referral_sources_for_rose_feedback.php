<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $now = now();

            $this->renameReferral('Brochure', 'Newspaper / magazine / brochure', $now);
            $this->renameReferral('TV or Newspaper', 'TV and radio', $now);

            foreach ([
                'Word of mouth',
                'Accommodation',
                'Road sign',
                'Other',
            ] as $name) {
                $this->restoreOrCreateReferral($name, $now);
            }

            DB::table('type_referrals')
                ->where('name', 'Travel Guide')
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $now = now();

            $this->renameReferral('Newspaper / magazine / brochure', 'Brochure', $now);
            $this->renameReferral('TV and radio', 'TV or Newspaper', $now);

            DB::table('type_referrals')
                ->where('name', 'Travel Guide')
                ->update([
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);

            DB::table('type_referrals')
                ->whereIn('name', [
                    'Word of mouth',
                    'Accommodation',
                    'Road sign',
                    'Other',
                ])
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        });
    }

    private function renameReferral(string $from, string $to, $now): void
    {
        $fromRow = DB::table('type_referrals')->where('name', $from)->first();
        if (! $fromRow) {
            return;
        }

        $toRow = DB::table('type_referrals')->where('name', $to)->first();
        if ($toRow && (int) $toRow->id !== (int) $fromRow->id) {
            if (Schema::hasTable('booking_details') && Schema::hasColumn('booking_details', 'referral_source_id')) {
                DB::table('booking_details')
                    ->where('referral_source_id', $fromRow->id)
                    ->update(['referral_source_id' => $toRow->id]);
            }

            DB::table('type_referrals')
                ->where('id', $fromRow->id)
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('type_referrals')
            ->where('id', $fromRow->id)
            ->update([
                'name' => $to,
                'deleted_at' => null,
                'updated_at' => $now,
            ]);
    }

    private function restoreOrCreateReferral(string $name, $now): void
    {
        $existing = DB::table('type_referrals')->where('name', $name)->first();
        if ($existing) {
            DB::table('type_referrals')
                ->where('id', $existing->id)
                ->update([
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('type_referrals')->insert([
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
