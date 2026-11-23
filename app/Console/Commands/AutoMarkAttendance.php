<?php

namespace App\Console\Commands;

use App\Models\CustomerManifest;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoMarkAttendance extends Command
{
    protected $signature = 'attendance:auto-mark-arrived
        {--date= : Explicit activity date in YYYY-MM-DD format}
        {--team= : Limit the run to one enabled site/team ID}
        {--dry-run : Report matching manifests without changing them}';

    protected $description = 'Mark completed check-ins still listed as no-show as arrived for enabled sites.';

    public function handle(): int
    {
        $timezone = 'Africa/Johannesburg';
        $now = CarbonImmutable::now($timezone);
        $explicitDate = $this->validatedDate((string) $this->option('date'), $timezone);

        if ($this->option('date') && ! $explicitDate) {
            $this->error('The --date value must use YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $teamId = $this->option('team');
        if ($teamId !== null && ! filter_var($teamId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $this->error('The --team value must be a positive integer.');

            return self::FAILURE;
        }

        $teams = Team::query()
            ->where('auto_attendance_enabled', true)
            ->when($teamId, fn (Builder $query) => $query->whereKey((int) $teamId))
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'auto_attendance_timing',
                'auto_attendance_run_time',
            ]);

        if ($teamId && $teams->isEmpty()) {
            $this->warn("Team {$teamId} was not found, is inactive, or has automatic attendance disabled.");

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($teams as $team) {
            if (! $explicitDate && ! $this->isDueNow($team, $now)) {
                continue;
            }

            $activityDate = $explicitDate ?: $this->scheduledActivityDate($team, $now);
            $manifestIds = $this->eligibleManifestIds($team, $activityDate);
            $count = $manifestIds->count();
            $total += $count;

            if (! $this->option('dry-run') && $count > 0) {
                DB::transaction(function () use ($manifestIds) {
                    CustomerManifest::query()
                        ->whereKey($manifestIds)
                        ->lockForUpdate()
                        ->get()
                        ->each(function (CustomerManifest $manifest) {
                            if ($manifest->attendance_status === CustomerManifest::ATTENDANCE_DEFAULT) {
                                $manifest->update(['attendance_status' => 'arrived']);
                            }
                        });
                });
            }

            $action = $this->option('dry-run') ? 'would be marked arrived' : 'marked arrived';
            $this->line("{$team->name}: {$count} manifest(s) {$action} for {$activityDate->toDateString()}.");

            if ($count > 0) {
                Log::info('Automatic attendance reconciliation completed', [
                    'team_id' => $team->id,
                    'activity_date' => $activityDate->toDateString(),
                    'matched' => $count,
                    'dry_run' => (bool) $this->option('dry-run'),
                ]);
            }
        }

        if ($explicitDate || $total > 0) {
            $this->info("Total: {$total} manifest(s).");
        }

        return self::SUCCESS;
    }

    private function validatedDate(string $value, string $timezone): ?CarbonImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);

            return $date && $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function isDueNow(Team $team, CarbonImmutable $now): bool
    {
        return substr((string) $team->auto_attendance_run_time, 0, 5) === $now->format('H:i');
    }

    private function scheduledActivityDate(Team $team, CarbonImmutable $now): CarbonImmutable
    {
        return $team->auto_attendance_timing === 'next_day'
            ? $now->subDay()->startOfDay()
            : $now->startOfDay();
    }

    private function eligibleManifestIds(Team $team, CarbonImmutable $activityDate)
    {
        return CustomerManifest::query()
            ->where('customer_manifests.team_id', $team->id)
            ->where('customer_manifests.is_indemnity', true)
            ->where('customer_manifests.attendance_status', CustomerManifest::ATTENDANCE_DEFAULT)
            ->whereNull('customer_manifests.deleted_at')
            ->whereHas('booking_item', function (Builder $bookingItem) use ($team, $activityDate) {
                $bookingItem
                    ->where('team_id', $team->id)
                    ->whereNull('deleted_at')
                    ->whereHas('booking', fn (Builder $booking) => $booking
                        ->where('team_id', $team->id)
                        ->whereNull('deleted_at')
                        ->operational())
                    ->whereHas('booking_inventory', fn (Builder $inventory) => $inventory
                        ->whereDate('date', $activityDate->toDateString())
                        ->whereNull('deleted_at'));
            })
            ->pluck('customer_manifests.id');
    }
}
