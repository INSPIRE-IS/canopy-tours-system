<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\GatewayLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpireUnpaidFrontendBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid-front
        {--hours= : Override hold window in hours. By default each team uses its admin site setting, falling back to 3 hours}
        {--team= : Optional team id}
        {--force : Actually expire bookings. Without this option the command is a dry run}';

    protected $description = 'Expire unpaid public frontend provisional bookings so abandoned carts release capacity.';

    public function handle(): int
    {
        $hoursOverride = $this->option('hours') !== null && $this->option('hours') !== '';
        $hoursOverrideValue = $hoursOverride ? max(0, (int) $this->option('hours')) : null;
        $teamId = $this->option('team') !== null ? (int) $this->option('team') : null;
        $force = (bool) $this->option('force');
        $now = now();

        $bookings = Booking::query()
            ->with([
                'customer:id,full_name,email_address,contact_number',
                'booking_items:id,booking_id,service_id,season_id,team_id',
                'booking_items.service:id,name',
                'booking_items.booking_inventory:id,booking_item_id,date,inventory_time_slot_id,pax',
                'booking_items.booking_inventory.inventory_time_slot:id,start_time',
                'team:id,frontend_unpaid_booking_hold_hours',
            ])
            ->whereNotNull('session_id')
            ->whereNull('user_id')
            ->where('status_payment_id', 1)
            ->whereRaw('COALESCE(tot_paid, 0) = 0')
            ->whereNull('payment_window_expired_at')
            ->whereNull('payment_window_restored_at')
            ->whereNull('deleted_at')
            ->when($teamId, fn($query) => $query->where('team_id', $teamId))
            ->whereDoesntHave('booking_payments', function ($query) {
                $query->where('status_payment_id', 2);
            })
            ->orderBy('created_at')
            ->get();

        $rows = [];
        $candidateCount = 0;
        $expiredCandidateCount = 0;
        $expiredCount = 0;

        foreach ($bookings as $booking) {
            $siteHours = $booking->team?->frontend_unpaid_booking_hold_hours;

            // A deliberate site value of zero remains authoritative even when
            // support runs the command with a temporary --hours override.
            if ($siteHours !== null && (int) $siteHours === 0) {
                continue;
            }

            $hours = $hoursOverrideValue
                ?? max(0, (int) ($siteHours ?? 3));

            if ($hours === 0) {
                continue;
            }

            $candidateCount++;
            $latestSubmitAt = GatewayLog::where('booking_id', $booking->id)
                ->where('event', 'submit')
                ->max('created_at');

            $holdFrom = ($latestSubmitAt
                ? Carbon::parse($latestSubmitAt)
                : $booking->created_at->copy())
                ->setTimezone('Africa/Johannesburg');
            $expiresAt = $holdFrom->copy()->addHours($hours);
            $expired = $expiresAt->lte($now->copy()->setTimezone('Africa/Johannesburg'));

            if (! $expired) {
                continue;
            }

            $row = [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'team_id' => $booking->team_id,
                'lead_name' => $booking->lead_name,
                'email' => $booking->customer?->email_address,
                'phone' => $booking->customer?->contact_number,
                'created_at' => optional($booking->created_at)->format('Y-m-d H:i:s'),
                'latest_payfast_submit_at' => $latestSubmitAt ? Carbon::parse($latestSubmitAt)->format('Y-m-d H:i:s') : '',
                'hold_from' => $holdFrom->format('Y-m-d H:i:s'),
                'hold_hours' => $hours,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'expired' => $expired ? 'yes' : 'no',
                'action' => $expired ? ($force ? 'marked_expired' : 'would_mark_expired') : 'kept',
                'grand_tot_due' => $booking->grand_tot_due,
                'activity_count' => $booking->booking_items->count(),
                'activities' => $this->formatActivities($booking),
            ];

            $rows[] = $row;
            $expiredCandidateCount++;

            if ($expired && $force) {
                $this->expireBooking($booking, $hours, $holdFrom, $expiresAt);
                $expiredCount++;
            }
        }

        $reportPath = count($rows) > 0 ? $this->writeCsv($rows) : null;

        $this->info(($force ? 'Expired' : 'Would expire') . " " . ($force ? $expiredCount : $expiredCandidateCount) . " booking(s). Active provisional bookings checked: {$candidateCount}");
        $this->info($reportPath ? "CSV report: storage/app/{$reportPath}" : 'No CSV report written because no bookings were expired or eligible to expire.');

        return self::SUCCESS;
    }

    private function expireBooking(Booking $booking, int $hours, Carbon $holdFrom, Carbon $expiresAt): void
    {
        DB::transaction(function () use ($booking, $hours, $holdFrom, $expiresAt) {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ((int) $booking->status_payment_id !== 1
                || (float) $booking->tot_paid > 0
                || $booking->payment_window_expired_at !== null
                || $booking->payment_window_restored_at !== null
                || $booking->session_id === null
                || $booking->user_id !== null
                || $booking->booking_payments()->where('status_payment_id', 2)->exists()) {
                return;
            }

            $booking->update([
                'payment_window_expired_at' => now(),
            ]);

            AuditLog::create([
                'description' => 'Frontend unpaid booking payment window expired',
                'subject_id' => $booking->id,
                'subject_type' => Booking::class,
                'user_id' => null,
                'properties' => [
                    'reference' => $booking->reference,
                    'team_id' => $booking->team_id,
                    'hours' => $hours,
                    'hold_from' => $holdFrom->format('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                    'payment_window_expired_at' => $booking->payment_window_expired_at?->format('Y-m-d H:i:s'),
                    'reason' => 'Unpaid frontend provisional booking exceeded hold window.',
                ],
                'host' => 'console',
            ]);
        });
    }

    private function formatActivities(Booking $booking): string
    {
        return $booking->booking_items
            ->map(function (BookingItem $item) {
                $inventory = $item->booking_inventory;
                $time = $inventory?->inventory_time_slot?->start_time;

                return implode(' | ', array_filter([
                    $item->service?->name,
                    $inventory?->date,
                    $time ? substr($time, 0, 5) : null,
                    $inventory?->pax !== null ? 'pax ' . $inventory->pax : null,
                ]));
            })
            ->filter()
            ->implode('; ');
    }

    private function writeCsv(array $rows): string
    {
        Storage::disk('local')->makeDirectory('reports');
        $path = 'reports/unpaid-front-booking-expiry-' . now('Africa/Johannesburg')->format('Ymd-His') . '.csv';
        $handle = fopen(Storage::disk('local')->path($path), 'w');

        $headers = [
            'booking_id',
            'reference',
            'team_id',
            'lead_name',
            'email',
            'phone',
            'created_at',
            'latest_payfast_submit_at',
            'hold_from',
            'hold_hours',
            'expires_at',
            'expired',
            'action',
            'grand_tot_due',
            'activity_count',
            'activities',
        ];

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn($header) => $row[$header] ?? '', $headers));
        }

        fclose($handle);

        return $path;
    }
}
