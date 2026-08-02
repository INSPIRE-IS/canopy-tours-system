<?php

namespace App\Console\Commands;

use App\Models\Alert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAlertContext extends Command
{
    protected $signature = 'alerts:backfill-context
        {--team= : Limit to a team id}
        {--dry-run : Show matches without saving}
        {--window=30 : Created-at matching window in minutes for alerts without a booking/customer in the text}';

    // php artisan alerts:backfill-context --dry-run --team=3 --window=60

    protected $description = 'Best-effort backfill of booking/customer/manifest context on old check-in alerts.';

    public function handle(): int
    {
        $teamId = $this->option('team') ? (int) $this->option('team') : null;
        $dryRun = (bool) $this->option('dry-run');
        $window = max(1, (int) $this->option('window'));

        $query = Alert::query()
            ->whereIn('type_alert_id', [5, 7, 8, 9, 11])
            ->where(function ($query) {
                $query->whereNull('booking_id')
                    ->orWhereNull('booking_item_id')
                    ->orWhereNull('customer_id')
                    ->orWhereNull('customer_manifest_id');
            })
            ->orderBy('id');

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $alerts = $query->get();
        $this->info('Alerts to inspect: ' . $alerts->count());

        $updated = 0;
        $skipped = 0;
        $rows = [];

        DB::beginTransaction();

        try {
            foreach ($alerts as $alert) {
                $candidates = $this->candidatesForAlert($alert, $window);

                if ($candidates->count() !== 1) {
                    $skipped++;
                    $rows[] = [
                        $alert->id,
                        $this->labelForType((int) $alert->type_alert_id),
                        $candidates->count(),
                        'skipped',
                        $alert->alert_text,
                    ];
                    continue;
                }

                $match = $candidates->first();
                $rows[] = [
                    $alert->id,
                    $this->labelForType((int) $alert->type_alert_id),
                    1,
                    $dryRun ? 'would update' : 'updated',
                    "{$match->full_name} / {$match->reference}",
                ];

                if (!$dryRun) {
                    $alert->update([
                        'booking_id' => $match->booking_id,
                        'booking_item_id' => $match->booking_item_id,
                        'customer_id' => $match->customer_id,
                        'customer_manifest_id' => $match->customer_manifest_id,
                        'alert_link' => route('admin.bookings.add', $match->booking_id, false),
                    ]);
                }

                $updated++;
            }

            $this->table(['Alert ID', 'Type', 'Matches', 'Action', 'Context'], $rows);
            $this->line("Updated: {$updated}");
            $this->line("Skipped: {$skipped}");

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete. Changes rolled back.');
                return self::SUCCESS;
            }

            DB::commit();
            $this->info('Backfill committed.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function candidatesForAlert(Alert $alert, int $window)
    {
        $bookingId = $this->bookingIdFromLink($alert->alert_link);
        $reference = $this->referenceFromText($alert->alert_text);

        $query = DB::table('question_answers as qa')
            ->join('questions as q', 'q.id', '=', 'qa.question_id')
            ->join('booking_items as bi', 'bi.id', '=', 'qa.booking_item_id')
            ->join('bookings as b', 'b.id', '=', 'bi.booking_id')
            ->join('customers as c', 'c.id', '=', 'qa.customer_id')
            ->leftJoin('customer_manifests as cm', function ($join) {
                $join->on('cm.booking_item_id', '=', 'qa.booking_item_id')
                    ->on('cm.customer_id', '=', 'qa.customer_id')
                    ->whereNull('cm.deleted_at');
            })
            ->where('b.team_id', $alert->team_id)
            ->whereNull('qa.deleted_at')
            ->whereNull('bi.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNotNull('cm.id')
            ->select([
                'b.id as booking_id',
                'b.reference',
                'bi.id as booking_item_id',
                'c.id as customer_id',
                'c.full_name',
                'cm.id as customer_manifest_id',
            ])
            ->distinct();

        if ($bookingId) {
            $query->where('b.id', $bookingId);
        }

        if ($reference) {
            $query->where('b.reference', $reference);
        }

        if (!$bookingId && !$reference) {
            $query->whereBetween('qa.updated_at', [
                $alert->created_at->copy()->subMinutes($window)->format('Y-m-d H:i:s'),
                $alert->created_at->copy()->addMinutes($window)->format('Y-m-d H:i:s'),
            ]);
        }

        $this->applyQuestionFilter($query, (int) $alert->type_alert_id);

        $candidates = $query->get();
        $text = (string) $alert->alert_text;

        // Newer alert text may contain "Reason: Customer / REF-123"; use that to reduce ambiguity.
        $nameMatches = $candidates->filter(fn($candidate) => $candidate->full_name && str_contains($text, $candidate->full_name));
        if ($nameMatches->count() === 1) {
            return $nameMatches->values();
        }

        return $candidates->values();
    }

    private function applyQuestionFilter($query, int $typeAlertId): void
    {
        match ($typeAlertId) {
            5 => $query->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('q.slug', 'medical_id')->where('qa.answer', '1');
                })->orWhere(function ($query) {
                    $query->where('q.slug', 'medical_detail')->whereNotNull('qa.answer')->where('qa.answer', '!=', '');
                });
            }),
            7 => $query->where('q.slug', 'is_fit')->where('qa.answer', '2'),
            8 => $query->where('q.slug', 'is_agree')->where('qa.answer', '2'),
            9 => $query->whereIn('q.slug', ['is_agree', 'signature', 'full_name', 'email_address']),
            11 => $query->where('q.slug', 'pregnant')->where('qa.answer', '1'),
            default => null,
        };
    }

    private function bookingIdFromLink(?string $link): ?int
    {
        if (!$link || !preg_match('~/admin/bookings/add/(\d+)~', $link, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function referenceFromText(?string $text): ?string
    {
        if (!$text || !preg_match('/REF-[A-Z0-9]+/i', $text, $matches)) {
            return null;
        }

        return strtoupper($matches[0]);
    }

    private function labelForType(int $typeAlertId): string
    {
        return match ($typeAlertId) {
            5 => 'Medical',
            7 => 'Not fit',
            8 => 'Terms not agreed',
            9 => 'Check-in complete',
            11 => 'Pregnancy',
            default => 'Unknown',
        };
    }
}
