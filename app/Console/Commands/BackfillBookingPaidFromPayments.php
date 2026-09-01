<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillBookingPaidFromPayments extends Command
{
    /*
     * Rebuild booking paid totals from real booking_payments rows.
     *
     * Use this when historical booking edits capped bookings.tot_paid or
     * booking_fees.tot_paid down to the edited booking value, hiding a credit.
     *
     * Dry run first:
     * php artisan bookings:backfill-paid-from-payments --team=3
     *
     * Apply after checking the dry-run output:
     * php artisan bookings:backfill-paid-from-payments --team=3 --apply
     *
     * Scope options:
     * php artisan bookings:backfill-paid-from-payments --booking=140
     * php artisan bookings:backfill-paid-from-payments --booking=REF-WVHQUY
     * php artisan bookings:backfill-paid-from-payments --from=2026-08-01 --to=2026-08-31
     *
     * Notes:
     * - Only real, non-deleted booking_payments are used.
     * - Booking payment amount is calculated as ex_vat_amount + vat_amount.
     * - additional_amount is deliberately excluded because it is for extras.
     * - Cancelled bookings are skipped unless --include-cancelled is passed.
     * - Prepaid voucher bookings without payment rows are not touched.
     */
    protected $signature = 'bookings:backfill-paid-from-payments
        {--team= : Only evaluate this team id}
        {--booking= : Booking id/reference, or comma-separated ids/references}
        {--from= : Only bookings with an activity date on/after this date (Y-m-d)}
        {--to= : Only bookings with an activity date on/before this date (Y-m-d)}
        {--include-cancelled : Include soft-deleted/cancelled bookings and booking items}
        {--apply : Persist changes. Without this option the command is a dry run}
        {--limit= : Limit the number of candidate bookings processed}
        {--tolerance=0.01 : Ignore differences smaller than this amount}';

    protected $description = 'Backfill booking paid totals from real booking payment rows and expose hidden credits.';

    public function handle(): int
    {
        $teamId = $this->option('team') ? (int) $this->option('team') : null;
        $bookingFilter = $this->option('booking') ? (string) $this->option('booking') : null;
        $from = $this->option('from') ? (string) $this->option('from') : null;
        $to = $this->option('to') ? (string) $this->option('to') : null;
        $includeCancelled = (bool) $this->option('include-cancelled');
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') ? max(1, (int) $this->option('limit')) : null;
        $tolerance = max(0.0, (float) $this->option('tolerance'));

        $candidates = $this->candidateQuery($teamId, $bookingFilter, $from, $to, $includeCancelled, $tolerance);
        $totalCandidates = (clone $candidates)->count();

        $this->info(sprintf(
            'Found %d booking(s) where stored paid total differs from payment rows.',
            $totalCandidates
        ));

        if ($totalCandidates === 0) {
            return self::SUCCESS;
        }

        if ($limit && $totalCandidates > $limit) {
            $this->warn("Limit is {$limit}; only the first {$limit} candidate(s) will be processed.");
        }

        $rows = $candidates
            ->orderBy('bookings.id')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get();

        $summary = $this->summarize($rows);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Candidates processed', $rows->count()],
                ['Would show credit', $summary['credit_count']],
                ['Would still be due', $summary['due_count']],
                ['Would be exactly paid', $summary['paid_count']],
                ['Negative payment totals skipped', $summary['negative_count']],
            ]
        );

        $this->line('Sample:');
        $this->table(
            ['booking_id', 'reference', 'total', 'stored_paid', 'payment_rows_paid', 'delta', 'result'],
            $rows->take(15)->map(fn ($row) => [
                $row->booking_id,
                $row->reference,
                $this->money($row->grand_total),
                $this->money($row->stored_paid),
                $this->money($row->payment_total),
                $this->money(((float) $row->payment_total) - ((float) $row->stored_paid)),
                $this->balanceLabel((float) $row->grand_total, (float) $row->payment_total),
            ])->all()
        );

        if (!$apply) {
            $this->warn('Dry run only: no rows were updated. Re-run with --apply to backfill.');
            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $actualPaid = round((float) $row->payment_total, 2);
            if ($actualPaid < 0) {
                $skipped++;
                $this->warn("Skipped booking {$row->booking_id} ({$row->reference}) because payment rows total is negative.");
                continue;
            }

            DB::transaction(function () use ($row, $actualPaid, $includeCancelled, &$updated) {
                $oldPaid = round((float) $row->stored_paid, 2);
                $grandTotal = round((float) $row->grand_total, 2);

                DB::table('bookings')
                    ->where('id', $row->booking_id)
                    ->update([
                        'tot_paid' => $actualPaid,
                        'status_payment_id' => $grandTotal > 0 && $actualPaid >= $grandTotal ? 2 : 1,
                        'updated_at' => now(),
                    ]);

                $feeUpdates = $this->distributePaidToFees((int) $row->booking_id, $actualPaid, $includeCancelled);

                AuditLog::create([
                    'description' => 'Booking paid total backfilled from payment rows',
                    'subject_id' => $row->booking_id,
                    'subject_type' => Booking::class,
                    'user_id' => null,
                    'host' => 'console',
                    'properties' => [
                        'reference' => $row->reference,
                        'old_paid' => $oldPaid,
                        'new_paid' => $actualPaid,
                        'grand_total' => $grandTotal,
                        'credit' => max(0, round($actualPaid - $grandTotal, 2)),
                        'payment_rows' => (int) $row->payment_rows,
                        'fee_updates' => $feeUpdates,
                    ],
                ]);

                $updated++;
            });

            $this->line("Updated booking {$row->booking_id} ({$row->reference}) to paid {$this->money($actualPaid)}.");
        }

        $this->info("Done. Updated {$updated} booking(s). Skipped {$skipped} booking(s).");

        return self::SUCCESS;
    }

    private function candidateQuery(?int $teamId, ?string $bookingFilter, ?string $from, ?string $to, bool $includeCancelled, float $tolerance)
    {
        $paymentTotals = DB::table('booking_payments')
            ->select([
                'booking_id',
                DB::raw('ROUND(SUM(COALESCE(ex_vat_amount, 0) + COALESCE(vat_amount, 0)), 2) as payment_total'),
                DB::raw('COUNT(*) as payment_rows'),
            ])
            ->whereNull('deleted_at')
            ->groupBy('booking_id');

        $query = DB::table('bookings')
            ->joinSub($paymentTotals, 'payment_totals', function ($join) {
                $join->on('payment_totals.booking_id', '=', 'bookings.id');
            })
            ->select([
                'bookings.id as booking_id',
                'bookings.reference',
                'bookings.team_id',
                'bookings.grand_tot_due as grand_total',
                'bookings.tot_paid as stored_paid',
                'payment_totals.payment_total',
                'payment_totals.payment_rows',
            ])
            ->whereRaw('ABS(COALESCE(bookings.tot_paid, 0) - payment_totals.payment_total) > ?', [$tolerance]);

        if ($teamId) {
            $query->where('bookings.team_id', $teamId);
        }

        if (!$includeCancelled) {
            $query->whereNull('bookings.deleted_at');
        }

        if ($bookingFilter) {
            $this->applyBookingFilter($query, $bookingFilter);
        }

        if ($from || $to) {
            $query->whereExists(function ($subQuery) use ($from, $to, $includeCancelled) {
                $subQuery->selectRaw('1')
                    ->from('booking_items')
                    ->join('booking_inventories', 'booking_inventories.booking_item_id', '=', 'booking_items.id')
                    ->whereColumn('booking_items.booking_id', 'bookings.id');

                if (!$includeCancelled) {
                    $subQuery->whereNull('booking_items.deleted_at')
                        ->whereNull('booking_inventories.deleted_at');
                }

                if ($from) {
                    $subQuery->whereDate('booking_inventories.date', '>=', $from);
                }

                if ($to) {
                    $subQuery->whereDate('booking_inventories.date', '<=', $to);
                }
            });
        }

        return $query;
    }

    private function applyBookingFilter($query, string $bookingFilter): void
    {
        $parts = collect(explode(',', $bookingFilter))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->values();

        $ids = $parts->filter(fn ($part) => ctype_digit($part))->map(fn ($part) => (int) $part)->all();
        $references = $parts->reject(fn ($part) => ctype_digit($part))->all();

        $query->where(function ($subQuery) use ($ids, $references) {
            if (!empty($ids)) {
                $subQuery->whereIn('bookings.id', $ids);
            }

            if (!empty($references)) {
                $method = !empty($ids) ? 'orWhereIn' : 'whereIn';
                $subQuery->{$method}('bookings.reference', $references);
            }
        });
    }

    private function distributePaidToFees(int $bookingId, float $actualPaid, bool $includeCancelled): array
    {
        $feesQuery = DB::table('booking_fees')
            ->join('booking_items', 'booking_items.id', '=', 'booking_fees.booking_item_id')
            ->where('booking_items.booking_id', $bookingId)
            ->select([
                'booking_fees.id',
                'booking_fees.tot_amount',
                'booking_fees.tot_paid',
            ])
            ->orderBy('booking_items.id')
            ->orderBy('booking_fees.id');

        if (!$includeCancelled) {
            $feesQuery->whereNull('booking_items.deleted_at')
                ->whereNull('booking_fees.deleted_at');
        }

        $fees = $feesQuery->get();
        if ($fees->isEmpty()) {
            return [];
        }

        $remaining = round($actualPaid, 2);
        $updates = [];

        foreach ($fees as $fee) {
            $feeTotal = round((float) ($fee->tot_amount ?? 0), 2);
            $paidForFee = min(max(0, $remaining), $feeTotal);
            $remaining = round($remaining - $paidForFee, 2);

            $updates[] = [
                'id' => (int) $fee->id,
                'old_paid' => round((float) ($fee->tot_paid ?? 0), 2),
                'new_paid' => round($paidForFee, 2),
            ];
        }

        if ($remaining > 0) {
            $lastIndex = count($updates) - 1;
            $updates[$lastIndex]['new_paid'] = round($updates[$lastIndex]['new_paid'] + $remaining, 2);
        }

        foreach ($updates as $update) {
            DB::table('booking_fees')
                ->where('id', $update['id'])
                ->update([
                    'tot_paid' => $update['new_paid'],
                    'updated_at' => now(),
                ]);
        }

        return $updates;
    }

    private function summarize(Collection $rows): array
    {
        return [
            'credit_count' => $rows->filter(fn ($row) => (float) $row->payment_total > (float) $row->grand_total)->count(),
            'due_count' => $rows->filter(fn ($row) => (float) $row->payment_total >= 0 && (float) $row->payment_total < (float) $row->grand_total)->count(),
            'paid_count' => $rows->filter(fn ($row) => abs((float) $row->payment_total - (float) $row->grand_total) <= 0.01)->count(),
            'negative_count' => $rows->filter(fn ($row) => (float) $row->payment_total < 0)->count(),
        ];
    }

    private function balanceLabel(float $total, float $paid): string
    {
        $balance = round($total - $paid, 2);

        if ($balance > 0) {
            return 'Due ' . $this->money($balance);
        }

        if ($balance < 0) {
            return 'Credit ' . $this->money(abs($balance));
        }

        return 'Paid';
    }

    private function money(float $amount): string
    {
        return 'R' . number_format($amount, 2, '.', '');
    }
}
