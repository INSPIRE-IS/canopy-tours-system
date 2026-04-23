<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBookingItemTeamId extends Command
{
    // Usage:
    // # See what will change (no DB writes) + prints a small sample
    // php artisan bookings:backfill-item-team-id --dry-run

    // # Run it for everything (fills booking_items.team_id from bookings.team_id where team_id is NULL)
    // php artisan bookings:backfill-item-team-id

    // # Optional: only for one site/team
    // php artisan bookings:backfill-item-team-id --team=5

    // # Optional: smaller batches
    // php artisan bookings:backfill-item-team-id --chunk=200

    protected $signature = 'bookings:backfill-item-team-id
        {--team= : Only backfill bookings that belong to this team id}
        {--chunk=500 : How many booking_items to update per batch}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Backfill booking_items.team_id from bookings.team_id for legacy records (needed for team-scoped admin listings).';

    public function handle(): int
    {
        $teamId = $this->option('team') ? (int) $this->option('team') : null;
        $chunkSize = max(1, (int) ($this->option('chunk') ?? 500));
        $dryRun = (bool) $this->option('dry-run');

        $baseQuery = DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->whereNull('booking_items.team_id')
            ->whereNotNull('bookings.team_id');

        if ($teamId) {
            $baseQuery->where('bookings.team_id', $teamId);
        }

        $total = (clone $baseQuery)->count('booking_items.id');
        if ($total === 0) {
            $this->info('No booking_items with NULL team_id found (nothing to backfill).');
            return self::SUCCESS;
        }

        $this->info("Found {$total} booking_items with NULL team_id that can be backfilled.");

        $sample = (clone $baseQuery)
            ->select([
                'booking_items.id as booking_item_id',
                'booking_items.booking_id',
                'bookings.team_id as booking_team_id',
            ])
            ->orderBy('booking_items.id')
            ->limit(10)
            ->get();

        if ($sample->isNotEmpty()) {
            $this->line('Sample (first 10):');
            $this->table(
                ['booking_item_id', 'booking_id', 'booking_team_id'],
                $sample->map(fn($row) => [
                    $row->booking_item_id,
                    $row->booking_id,
                    $row->booking_team_id,
                ])->all()
            );
        }

        if ($dryRun) {
            $this->warn('Dry run enabled: no rows were updated.');
            return self::SUCCESS;
        }

        $updatedTotal = 0;
        $lastId = 0;

        while (true) {
            $ids = (clone $baseQuery)
                ->where('booking_items.id', '>', $lastId)
                ->orderBy('booking_items.id')
                ->limit($chunkSize)
                ->pluck('booking_items.id')
                ->all();

            if (empty($ids)) {
                break;
            }

            $lastId = max($ids);

            // Join update ensures each booking_item gets the correct team_id (from its booking).
            $updated = DB::table('booking_items')
                ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
                ->whereIn('booking_items.id', $ids)
                ->update([
                    'booking_items.team_id' => DB::raw('bookings.team_id'),
                ]);

            $updatedTotal += (int) $updated;
            $this->line("Updated {$updated} booking_items (up to booking_items.id={$lastId}).");
        }

        $remaining = DB::table('booking_items')->whereNull('team_id')->count('id');
        $this->info("Done. Updated {$updatedTotal} booking_items. Remaining NULL team_id rows: {$remaining}.");

        return self::SUCCESS;
    }
}
