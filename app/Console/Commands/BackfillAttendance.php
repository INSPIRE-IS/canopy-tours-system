<?php

namespace App\Console\Commands;

use App\Models\CustomerManifest;
use App\Models\Team;
use App\Services\AttendanceReconciliationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackfillAttendance extends Command
{
    protected $signature = 'attendance:backfill-arrived
        {--team= : Required site/team ID}
        {--from= : Required inclusive start date in YYYY-MM-DD format}
        {--to= : Required inclusive end date in YYYY-MM-DD format}
        {--apply : Persist the reviewed changes; otherwise this is a dry run}';

    protected $description = 'Review or apply a historical arrived-attendance backfill for completed check-ins.';

    public function handle(AttendanceReconciliationService $attendance): int
    {
        $timezone = 'Africa/Johannesburg';
        $teamId = filter_var($this->option('team'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $from = $this->validatedDate((string) $this->option('from'), $timezone);
        $to = $this->validatedDate((string) $this->option('to'), $timezone);

        if (! $teamId) {
            $this->error('The --team option is required and must be a positive integer.');

            return self::FAILURE;
        }
        if (! $from || ! $to) {
            $this->error('The --from and --to options are required and must use YYYY-MM-DD format.');

            return self::FAILURE;
        }
        if ($from->greaterThan($to)) {
            $this->error('The --from date cannot be after the --to date.');

            return self::FAILURE;
        }
        if ($to->greaterThan(CarbonImmutable::now($timezone)->startOfDay())) {
            $this->error('The attendance backfill cannot include a future activity date.');

            return self::FAILURE;
        }

        $team = Team::query()->find($teamId);
        if (! $team) {
            $this->error("Active team {$teamId} was not found.");

            return self::FAILURE;
        }

        $candidates = $attendance->eligibleManifestQuery($team->id, $from, $to)
            ->orderBy('customer_manifests.id')
            ->get();
        $reportPath = $this->writeCsv($team, $from, $to, $candidates);

        $this->line("{$team->name}: {$candidates->count()} completed check-in manifest(s) are eligible between {$from->toDateString()} and {$to->toDateString()}.");
        $this->line('Review CSV: '.Storage::disk('local')->path($reportPath));

        if (! $this->option('apply')) {
            $this->warn('Dry run only: no attendance rows were updated. Re-run with --apply after reviewing the CSV.');

            return self::SUCCESS;
        }

        $updated = $attendance->markArrived($candidates->pluck('id'));
        $this->info("Applied: {$updated} manifest(s) marked arrived.");

        Log::info('Historical attendance backfill completed', [
            'team_id' => $team->id,
            'activity_from' => $from->toDateString(),
            'activity_to' => $to->toDateString(),
            'candidates' => $candidates->count(),
            'updated' => $updated,
            'report_path' => $reportPath,
        ]);

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

    private function writeCsv(Team $team, CarbonImmutable $from, CarbonImmutable $to, $candidates): string
    {
        Storage::disk('local')->makeDirectory('reports');
        $path = sprintf(
            'reports/attendance-backfill-team-%d-%s-to-%s-%s.csv',
            $team->id,
            $from->format('Ymd'),
            $to->format('Ymd'),
            now('Africa/Johannesburg')->format('Ymd-His'),
        );
        $handle = fopen(Storage::disk('local')->path($path), 'w');
        $headers = [
            'manifest_id',
            'team_id',
            'activity_date',
            'start_time',
            'booking_id',
            'booking_reference',
            'booking_item_id',
            'customer_id',
            'guest_name',
            'current_attendance_status',
            'action',
        ];

        fputcsv($handle, $headers);
        foreach ($candidates as $manifest) {
            $bookingItem = $manifest->booking_item;
            $bookingInventory = $bookingItem?->booking_inventory;
            $booking = $bookingItem?->booking;
            $row = [
                'manifest_id' => $manifest->id,
                'team_id' => $manifest->team_id,
                'activity_date' => $bookingInventory?->getRawOriginal('date'),
                'start_time' => $bookingInventory?->inventory_time_slot?->start_time,
                'booking_id' => $booking?->id,
                'booking_reference' => $booking?->reference,
                'booking_item_id' => $bookingItem?->id,
                'customer_id' => $manifest->customer_id,
                'guest_name' => $manifest->customer?->full_name,
                'current_attendance_status' => $manifest->attendance_status,
                'action' => 'mark_arrived',
            ];

            fputcsv($handle, array_map(fn ($header) => $row[$header] ?? '', $headers));
        }

        fclose($handle);

        return $path;
    }
}
