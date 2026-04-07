<?php

namespace App\Console\Commands;

use App\Models\LookupItem;
use App\Models\Team;
use Illuminate\Console\Command;

class BackfillSettings extends Command
{
    protected $signature = 'settings:backfill {--team= : Backfill a single team id}';

    protected $description = 'Ensure settings lookup rows exist for all teams.';

    public function handle(): int
    {
        $teamId = $this->option('team');

        LookupItem::ensureSettingsForTeam(null);
        $lookupId = \App\Models\Lookup::where('slug', 'settings')->value('id');
        if (!$lookupId) {
            $this->warn('Settings lookup not found.');
            return self::FAILURE;
        }

        if ($teamId) {
            LookupItem::ensureSettingsForTeam((int) $teamId);
            LookupItem::where('lookup_id', $lookupId)
                ->where('team_id', (int) $teamId)
                ->update([
                    'value' => 0,
                    'multiplier' => null,
                ]);
            LookupItem::where('lookup_id', $lookupId)
                ->whereNull('team_id')
                ->update([
                    'value' => 0,
                    'multiplier' => null,
                ]);
            $this->info("Settings backfilled for team {$teamId}.");
            return self::SUCCESS;
        }

        $teams = Team::select('id')->get();
        foreach ($teams as $team) {
            LookupItem::ensureSettingsForTeam($team->id);
            LookupItem::where('lookup_id', $lookupId)
                ->where('team_id', $team->id)
                ->update([
                    'value' => 0,
                    'multiplier' => null,
                ]);
        }
        LookupItem::where('lookup_id', $lookupId)
            ->whereNull('team_id')
            ->update([
                'value' => 0,
                'multiplier' => null,
            ]);

        $this->info('Settings backfilled for all teams.');

        return self::SUCCESS;
    }
}
