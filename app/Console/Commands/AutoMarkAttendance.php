<?php

namespace App\Console\Commands;

use App\Mail\AttendanceAnomalyMail;
use App\Models\Team;
use App\Services\AttendanceReconciliationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoMarkAttendance extends Command
{
    protected $signature = 'attendance:auto-mark-arrived
        {--date= : Explicit activity date in YYYY-MM-DD format}
        {--team= : Limit the run to one enabled site/team ID}
        {--dry-run : Report matching manifests without changing them}';

    protected $description = 'Reconcile completed check-ins and alert about missed arrivals.';

    public function handle(AttendanceReconciliationService $attendance): int
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
        $alertDeliveryFailed = false;

        foreach ($teams as $team) {
            $isScheduledRun = ! $explicitDate && $this->isDueNow($team, $now);
            $scheduledTimePassed = ! $explicitDate && $this->hasScheduledTimePassed($team, $now);

            if (! $explicitDate && ! $isScheduledRun && ! $scheduledTimePassed) {
                continue;
            }

            $activityDate = $explicitDate ?: $this->scheduledActivityDate($team, $now);
            $manifestIds = $attendance->eligibleManifestIds($team->id, $activityDate, $activityDate);
            $count = $manifestIds->count();

            if ($explicitDate || $isScheduledRun) {
                $total += $count;

                if (! $this->option('dry-run') && $count > 0) {
                    $attendance->markArrived($manifestIds);
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

            if (! $this->option('dry-run') && ($isScheduledRun || $scheduledTimePassed)) {
                if (! $this->alertForMissedArrivals($attendance, $team, $activityDate)) {
                    $alertDeliveryFailed = true;
                }

                if (! $this->alertForHistoricalArrivals($attendance, $team, $activityDate)) {
                    $alertDeliveryFailed = true;
                }
            }
        }

        if ($explicitDate || $total > 0) {
            $this->info("Total: {$total} manifest(s).");
        }

        return $alertDeliveryFailed ? self::FAILURE : self::SUCCESS;
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

    private function hasScheduledTimePassed(Team $team, CarbonImmutable $now): bool
    {
        return $now->format('H:i') > substr((string) $team->auto_attendance_run_time, 0, 5);
    }

    private function scheduledActivityDate(Team $team, CarbonImmutable $now): CarbonImmutable
    {
        return $team->auto_attendance_timing === 'next_day'
            ? $now->subDay()->startOfDay()
            : $now->startOfDay();
    }

    private function alertForMissedArrivals(
        AttendanceReconciliationService $attendance,
        Team $team,
        CarbonImmutable $activityDate,
    ): bool {
        $manifestIds = $attendance->eligibleManifestIds($team->id, $activityDate, $activityDate);
        $count = $manifestIds->count();
        $alertKey = sprintf(
            'attendance:missed-arrivals-alert:%d:%s',
            $team->id,
            $activityDate->toDateString(),
        );

        if ($count === 0) {
            Cache::forget($alertKey);

            return true;
        }

        if (Cache::has($alertKey)) {
            return true;
        }

        $recipient = trim((string) config('mail.audit_bcc'));
        if ($recipient === '') {
            $this->error('MAIL_AUDIT_BCC is not configured; missed-arrival alert was not sent.');
            Log::error('Missed-arrival alert could not be sent because MAIL_AUDIT_BCC is empty.', [
                'team_id' => $team->id,
                'activity_date' => $activityDate->toDateString(),
                'candidate_count' => $count,
            ]);

            return false;
        }

        Mail::to($recipient)->send(new AttendanceAnomalyMail(
            teamName: $team->name,
            teamId: $team->id,
            activityFrom: $activityDate->toDateString(),
            activityTo: $activityDate->toDateString(),
            candidateCount: $count,
            activityDateCounts: [$activityDate->toDateString() => $count],
        ));

        Cache::put($alertKey, true, now()->addDay());

        Log::warning('Missed-arrival attendance anomaly email sent.', [
            'team_id' => $team->id,
            'activity_date' => $activityDate->toDateString(),
            'candidate_count' => $count,
        ]);

        return true;
    }

    private function alertForHistoricalArrivals(
        AttendanceReconciliationService $attendance,
        Team $team,
        CarbonImmutable $activityDate,
    ): bool {
        $scanKey = sprintf(
            'attendance:historical-anomaly-scan:%d:%s',
            $team->id,
            $activityDate->toDateString(),
        );

        if (Cache::has($scanKey)) {
            return true;
        }

        $countsByDate = $attendance->eligibleManifestCountsByDate(
            $team->id,
            $activityDate->subDay(),
        );
        $alertKey = sprintf('attendance:historical-anomaly-alert:%d', $team->id);

        if ($countsByDate->isEmpty()) {
            Cache::put($scanKey, true, now()->addDay());
            Cache::forget($alertKey);

            return true;
        }

        if (Cache::has($alertKey)) {
            Cache::put($scanKey, true, now()->addDay());

            return true;
        }

        $recipient = trim((string) config('mail.audit_bcc'));
        if ($recipient === '') {
            $this->error('MAIL_AUDIT_BCC is not configured; historical attendance alert was not sent.');
            Log::error('Historical attendance alert could not be sent because MAIL_AUDIT_BCC is empty.', [
                'team_id' => $team->id,
                'activity_through' => $activityDate->subDay()->toDateString(),
                'activity_dates' => $countsByDate->count(),
            ]);

            return false;
        }

        $activityDateCounts = $countsByDate
            ->mapWithKeys(fn ($row) => [$row->activity_date => (int) $row->candidate_count])
            ->all();
        $dateKeys = array_keys($activityDateCounts);
        $dateFrom = CarbonImmutable::parse(min($dateKeys), 'Africa/Johannesburg')->toDateString();
        $dateTo = CarbonImmutable::parse(max($dateKeys), 'Africa/Johannesburg')->toDateString();
        $candidateCount = array_sum($activityDateCounts);

        Mail::to($recipient)->send(new AttendanceAnomalyMail(
            teamName: $team->name,
            teamId: $team->id,
            activityFrom: $dateFrom,
            activityTo: $dateTo,
            candidateCount: $candidateCount,
            activityDateCounts: $activityDateCounts,
            historical: true,
        ));

        Cache::forever($alertKey, true);
        Cache::put($scanKey, true, now()->addDay());

        Log::warning('Historical attendance anomaly email sent.', [
            'team_id' => $team->id,
            'activity_from' => $dateFrom,
            'activity_to' => $dateTo,
            'activity_dates' => count($activityDateCounts),
            'candidate_count' => $candidateCount,
        ]);

        return true;
    }
}
