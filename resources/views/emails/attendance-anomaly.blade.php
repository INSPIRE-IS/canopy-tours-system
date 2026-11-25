An attendance anomaly was detected.

Site: {{ $teamName }} (ID {{ $teamId }})
Activity range: {{ $activityFrom }} to {{ $activityTo }}
{{ $historical ? 'Historical candidates' : 'Eligible completed check-ins still marked no-show' }}: {{ $candidateCount }}

@foreach ($activityDateCounts as $date => $count)
{{ $date }}: {{ $count }}
@endforeach

The scheduler has not changed these records. Review them with the dry-run command:
php artisan attendance:backfill-arrived --team={{ $teamId }} --from={{ $activityFrom }} --to={{ $activityTo }}

After reviewing the CSV report, apply the backfill explicitly with --apply.
