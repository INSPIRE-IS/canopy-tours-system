<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SiteResetCommand extends Command
{
    // Usage:
    // php artisan site:reset --team=3 --dry-run
    // php artisan site:reset --team=3
    // php artisan site:reset --all --dry-run
    protected $signature = 'site:reset
        {--team= : Team ID to reset}
        {--all : Reset all teams}
        {--dry-run : Show what would be deleted without writing}
    ';

    protected $description = 'Reset a site/team to a clean slate (delete operational data + services/seasons/inventory/closeouts/promos/discounts). Keeps teams and users. Does not delete templates.';

    public function handle(): int
    {
        $teamId = $this->option('team') !== null ? (int) $this->option('team') : null;
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        if (($teamId && $all) || (!$teamId && !$all)) {
            $this->error('Provide exactly one of: --team=ID OR --all');
            return self::FAILURE;
        }

        $teamIds = $all
            ? Team::query()->pluck('id')->all()
            : [$teamId];

        if (empty($teamIds)) {
            $this->warn('No teams found to reset.');
            return self::SUCCESS;
        }

        if (!$dryRun) {
            $confirm = $all ? 'RESET ALL TEAMS' : 'RESET TEAM ' . $teamId;
            $typed = $this->ask("Type '{$confirm}' to confirm");
            if ($typed !== $confirm) {
                $this->warn('Confirmation mismatch. Aborting.');
                return self::FAILURE;
            }
        }

        foreach ($teamIds as $id) {
            $this->line('');
            $this->info('---');
            $this->info("Resetting team {$id}" . ($dryRun ? ' (dry-run)' : ''));
            $this->resetOneTeam((int) $id, $dryRun);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function resetOneTeam(int $teamId, bool $dryRun): void
    {
        $bookingIds = DB::table('bookings')->where('team_id', $teamId)->pluck('id')->all();

        $bookingItemIds = empty($bookingIds)
            ? []
            : DB::table('booking_items')->whereIn('booking_id', $bookingIds)->pluck('id')->all();

        $serviceIds = DB::table('services')->where('team_id', $teamId)->pluck('id')->all();
        $seasonIds = DB::table('seasons')->where('team_id', $teamId)->pluck('id')->all();
        $inventoryIds = DB::table('inventories')->where('team_id', $teamId)->pluck('id')->all();
        $wholesalerIds = DB::table('wholesalers')->where('team_id', $teamId)->pluck('id')->all();
        $promotionIds = DB::table('promotions')
            ->where(function ($q) use ($teamId, $serviceIds, $seasonIds) {
                $q->where('team_id', $teamId);
                if (!empty($serviceIds)) {
                    $q->orWhereIn('service_id', $serviceIds);
                }
                if (!empty($seasonIds)) {
                    $q->orWhereIn('season_id', $seasonIds);
                }
            })
            ->pluck('id')
            ->all();
        $closeoutIds = DB::table('closeouts')
            ->where(function ($q) use ($teamId, $serviceIds, $seasonIds) {
                $q->where('team_id', $teamId);
                if (!empty($serviceIds)) {
                    $q->orWhereIn('service_id', $serviceIds);
                }
                if (!empty($seasonIds)) {
                    $q->orWhereIn('season_id', $seasonIds);
                }
            })
            ->pluck('id')
            ->all();

        // Collect candidate customers for possible cleanup (customers are global, not team-scoped).
        $candidateCustomerIds = $this->collectCandidateCustomerIds($bookingIds, $bookingItemIds);

        $auditTypeStrings = $this->buildAuditSubjectTypeStrings(
            $bookingIds,
            $bookingItemIds,
            $serviceIds,
            $seasonIds,
            $inventoryIds,
            $wholesalerIds,
            $promotionIds,
            $closeoutIds
        );

        // Collect file paths before deleting file rows.
        // Important: files.booking_id is a FK to bookings, so we must delete *all* file rows
        // that reference bookings we are about to delete (even if type_file_id is null/legacy).
        $fileRows = DB::table('files')
            ->select(['id', 'path'])
            ->where(function ($q) use ($teamId, $bookingIds) {
                // Team-scoped operational files (exclude team branding like logo/image by type_file_id).
                $q->where(function ($sub) use ($teamId) {
                    $sub->where('team_id', $teamId)
                        ->whereIn('type_file_id', [3, 4, 5, 6, 7]); // service, invoice, checkin, booking_voucher, signature
                });

                // Booking-linked files (include any type_file_id; booking_id FK requires these be removed first).
                if (!empty($bookingIds)) {
                    $q->orWhereIn('booking_id', $bookingIds);
                }
            })
            ->get();

        $pathsToDelete = $fileRows
            ->pluck('path')
            ->filter(fn ($p) => is_string($p) && trim($p) !== '')
            ->values()
            ->all();

        $counts = $this->buildCounts($teamId, $bookingIds, $bookingItemIds, $serviceIds, $seasonIds, $inventoryIds);
        $counts['audit_logs'] = $this->countAuditLogs($bookingIds, $bookingItemIds, $auditTypeStrings);
        $this->printCounts($counts);

        if ($dryRun) {
            $this->line('Dry run: no deletes performed.');
            return;
        }

        DB::transaction(function () use (
            $teamId,
            $bookingIds,
            $bookingItemIds,
            $serviceIds,
            $seasonIds,
            $inventoryIds,
            $wholesalerIds,
            $promotionIds,
            $closeoutIds,
            $auditTypeStrings,
            $fileRows
        ) {
            // Audit logs: table is not team-scoped, so delete by subject references.
            $this->deleteAuditLogs($bookingIds, $bookingItemIds, $auditTypeStrings);

            // Operational (bookings/invoices/payments/manifests/emails/files)
            DB::table('payment_gateway_logs')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_id', $bookingIds ?: [0])
                ->delete();

            DB::table('emails')->where('team_id', $teamId)->delete();

            // Alerts/notifications are operational and team-scoped (alert_user pivot cascades on delete).
            DB::table('alerts')->where('team_id', $teamId)->delete();

            DB::table('booking_payments')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_id', $bookingIds ?: [0])
                ->delete();

            DB::table('invoices')->where('team_id', $teamId)->delete();

            DB::table('booking_notes')
                ->whereIn('booking_id', $bookingIds ?: [0])
                ->delete();

            // Question answers / questionnaires are constrained to booking_items without cascadeOnDelete.
            DB::table('question_answers')
                ->whereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();
            DB::table('booking_questionnaires')
                ->whereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('customer_manifests')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('booking_items_customer')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('booking_staff')
                ->whereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('booking_fees')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('booking_infos')
                ->whereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('booking_inventories')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            // booking_inventories has a FK to booking_details, so inventories must go first.
            DB::table('booking_details')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_item_id', $bookingItemIds ?: [0])
                ->delete();

            // Files has a FK to bookings, so remove file rows before deleting bookings.
            // Physical files are deleted after the transaction commits (best-effort).
            if ($fileRows->isNotEmpty()) {
                DB::table('files')->whereIn('id', $fileRows->pluck('id')->all())->delete();
            }

            // Additional tables that reference bookings via FK (must be removed before deleting bookings).
            DB::table('wholesaler_bookings')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_id', $bookingIds ?: [0])
                ->delete();

            DB::table('questionnaire_answers')
                ->whereIn('booking_id', $bookingIds ?: [0])
                ->delete();

            DB::table('gift_voucher_items')
                ->where('team_id', $teamId)
                ->orWhereIn('booking_id', $bookingIds ?: [0])
                ->delete();

            DB::table('booking_items')
                ->where('team_id', $teamId)
                ->orWhereIn('id', $bookingItemIds ?: [0])
                ->delete();

            DB::table('bookings')
                ->where('team_id', $teamId)
                ->orWhereIn('id', $bookingIds ?: [0])
                ->delete();

            // Setup (services/seasons/inventory/closeouts/promos/discounts/wholesalers)
            // Closeout slot pivots first (FK to closeouts / timeslots).
            DB::table('closeout_day_time_slots')
                ->where('team_id', $teamId)
                ->orWhereIn('closeout_id', $closeoutIds ?: [0])
                ->delete();

            // Promotion slot pivots first (FK to promotions).
            DB::table('promotion_day_time_slots')
                ->whereIn('promotion_id', $promotionIds ?: [0])
                ->delete();

            DB::table('promotions')
                ->where('team_id', $teamId)
                ->orWhereIn('service_id', $serviceIds ?: [0])
                ->orWhereIn('season_id', $seasonIds ?: [0])
                ->delete();

            // Vouchers can reference seasons/services via FK without cascade, so delete before seasons/services.
            DB::table('vouchers')
                ->where('team_id', $teamId)
                ->orWhereIn('service_id', $serviceIds ?: [0])
                ->orWhereIn('season_id', $seasonIds ?: [0])
                ->delete();

            // Discount-service pivots, then discounts.
            if (!empty($serviceIds)) {
                DB::table('discount_services')->whereIn('service_id', $serviceIds)->delete();
            }
            DB::table('discount_services')
                ->whereIn('discount_id', DB::table('discounts')->where('team_id', $teamId)->pluck('id')->all() ?: [0])
                ->delete();
            DB::table('discounts')->where('team_id', $teamId)->delete();

            // Wholesaler-related
            if (!empty($wholesalerIds)) {
                DB::table('wholesaler_contacts')->whereIn('wholesaler_id', $wholesalerIds)->delete();
            }
            DB::table('wholesaler_contacts')->where('team_id', $teamId)->delete();
            DB::table('wholesaler_rates')->where('team_id', $teamId)->delete();
            if (!empty($wholesalerIds)) {
                DB::table('wholesaler_rates')->whereIn('wholesaler_id', $wholesalerIds)->delete();
            }
            DB::table('wholesalers')->where('team_id', $teamId)->delete();

            // Gift voucher line items are team/service/season scoped even though the gift voucher header is global.
            DB::table('gift_voucher_items')
                ->where('team_id', $teamId)
                ->orWhereIn('service_id', $serviceIds ?: [0])
                ->orWhereIn('season_id', $seasonIds ?: [0])
                ->delete();

            // Inventory overrides / slot ranges / time slots / type days / inventories
            DB::table('inventory_slot_ranges')->where('team_id', $teamId)->delete();
            DB::table('inventory_overrides')->where('team_id', $teamId)->delete();

            if (!empty($inventoryIds)) {
                DB::table('inventory_slot_ranges')->whereIn('inventory_id', $inventoryIds)->delete();
                DB::table('inventory_time_slots')->whereIn('inventory_id', $inventoryIds)->delete();
                DB::table('inventory_type_days')->whereIn('inventory_id', $inventoryIds)->delete();
            }

            DB::table('inventory_time_slots')->where('team_id', $teamId)->delete();
            DB::table('inventory_type_days')->where('team_id', $teamId)->delete();
            DB::table('inventories')->where('team_id', $teamId)->delete();

            DB::table('closeouts')
                ->where('team_id', $teamId)
                ->orWhereIn('id', $closeoutIds ?: [0])
                ->orWhereIn('service_id', $serviceIds ?: [0])
                ->orWhereIn('season_id', $seasonIds ?: [0])
                ->delete();

            // Questionnaires (legacy builder) reference seasons/services via FK without cascade, so delete before seasons.
            DB::table('questionnaires')
                ->where('team_id', $teamId)
                ->orWhereIn('service_id', $serviceIds ?: [0])
                ->orWhereIn('season_id', $seasonIds ?: [0])
                ->delete();
            DB::table('seasons')->where('team_id', $teamId)->delete();

            if (!empty($serviceIds)) {
                DB::table('service_field_values')->whereIn('service_id', $serviceIds)->delete();
            }
            DB::table('services')->where('team_id', $teamId)->delete();

            // Booking vouchers are team-scoped (service/season/team). Clean them up too.
            DB::table('booking_vouchers')->where('team_id', $teamId)->delete();
        });

        // Physical file cleanup (best-effort; do not fail reset if a file is missing).
        $deletedFiles = 0;
        foreach ($pathsToDelete as $relativePath) {
            $fullPath = storage_path('app/public/' . ltrim($relativePath, '/'));
            try {
                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                    $deletedFiles++;
                }
            } catch (\Throwable $e) {
                Log::warning('site:reset file delete failed', [
                    'team_id' => $teamId,
                    'path' => $relativePath,
                    'message' => $e->getMessage(),
                ]);
            }
        }
        $this->line("Deleted files: {$deletedFiles} (best-effort).");

        // Customer cleanup: delete customers that became truly orphaned after the reset.
        $deletedCustomers = $this->deleteOrphanCustomers($candidateCustomerIds);
        $this->line("Deleted customers (orphaned): {$deletedCustomers}.");
    }

    private function buildAuditSubjectTypeStrings(
        array $bookingIds,
        array $bookingItemIds,
        array $serviceIds,
        array $seasonIds,
        array $inventoryIds,
        array $wholesalerIds,
        array $promotionIds,
        array $closeoutIds
    ): array {
        // Auditable trait stores subject_type as "Class#id". We delete those for objects we wipe for this team.
        $map = [
            'App\\Models\\Booking' => $bookingIds,
            'App\\Models\\BookingItem' => $bookingItemIds,
            'App\\Models\\Service' => $serviceIds,
            'App\\Models\\Season' => $seasonIds,
            'App\\Models\\Inventory' => $inventoryIds,
            'App\\Models\\Wholesaler' => $wholesalerIds,
            'App\\Models\\Promotion' => $promotionIds,
            'App\\Models\\Closeout' => $closeoutIds,
        ];

        $out = [];
        foreach ($map as $class => $ids) {
            foreach ($ids as $id) {
                $out[] = $class . '#' . (int) $id;
            }
        }

        return array_values(array_unique($out));
    }

    private function countAuditLogs(array $bookingIds, array $bookingItemIds, array $auditTypeStrings): int
    {
        return $this->countAuditLogsChunked($bookingIds, $bookingItemIds, $auditTypeStrings);
    }

    private function countAuditLogsChunked(array $bookingIds, array $bookingItemIds, array $auditTypeStrings): int
    {
        if (empty($bookingIds) && empty($bookingItemIds) && empty($auditTypeStrings)) {
            return 0;
        }

        $query = DB::table('audit_logs');
        $query->where(function ($q) use ($bookingIds, $bookingItemIds, $auditTypeStrings) {
            if (!empty($bookingIds)) {
                $q->orWhere(function ($qq) use ($bookingIds) {
                    $qq->where('subject_type', \App\Models\Booking::class)
                        ->whereIn('subject_id', $bookingIds);
                });
            }
            if (!empty($bookingItemIds)) {
                $q->orWhere(function ($qq) use ($bookingItemIds) {
                    $qq->where('subject_type', \App\Models\BookingItem::class)
                        ->whereIn('subject_id', $bookingItemIds);
                });
            }

            foreach (array_chunk($auditTypeStrings, 500) as $chunk) {
                $q->orWhereIn('subject_type', $chunk);
            }
        });

        return (int) $query->count();
    }

    private function deleteAuditLogs(array $bookingIds, array $bookingItemIds, array $auditTypeStrings): void
    {
        // Delete logs where subject_type is stored as class name with subject_id, used by manual AuditLog::create calls.
        if (!empty($bookingIds)) {
            DB::table('audit_logs')
                ->where('subject_type', \App\Models\Booking::class)
                ->whereIn('subject_id', $bookingIds)
                ->delete();
        }
        if (!empty($bookingItemIds)) {
            DB::table('audit_logs')
                ->where('subject_type', \App\Models\BookingItem::class)
                ->whereIn('subject_id', $bookingItemIds)
                ->delete();
        }

        // Delete logs where subject_type is stored as "Class#id", used by the Auditable trait.
        foreach (array_chunk($auditTypeStrings, 500) as $chunk) {
            DB::table('audit_logs')->whereIn('subject_type', $chunk)->delete();
        }
    }

    private function collectCandidateCustomerIds(array $bookingIds, array $bookingItemIds): array
    {
        $ids = collect();

        if (!empty($bookingIds)) {
            $ids = $ids->merge(DB::table('bookings')->whereIn('id', $bookingIds)->pluck('customer_id'));
        }
        if (!empty($bookingItemIds)) {
            $ids = $ids->merge(DB::table('booking_items_customer')->whereIn('booking_item_id', $bookingItemIds)->pluck('customer_id'));
            $ids = $ids->merge(DB::table('customer_manifests')->whereIn('booking_item_id', $bookingItemIds)->pluck('customer_id'));
            $ids = $ids->merge(DB::table('booking_questionnaires')->whereIn('booking_item_id', $bookingItemIds)->pluck('customer_id'));
            $ids = $ids->merge(DB::table('question_answers')->whereIn('booking_item_id', $bookingItemIds)->pluck('customer_id'));
        }

        return $ids
            ->filter(fn ($v) => !empty($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }

    private function deleteOrphanCustomers(array $candidateCustomerIds): int
    {
        if (empty($candidateCustomerIds)) {
            return 0;
        }

        $candidate = collect($candidateCustomerIds)->map(fn ($v) => (int) $v)->unique()->values();

        $stillReferenced = collect()
            ->merge(DB::table('bookings')->whereIn('customer_id', $candidate->all())->pluck('customer_id'))
            ->merge(DB::table('booking_items_customer')->whereIn('customer_id', $candidate->all())->pluck('customer_id'))
            ->merge(DB::table('customer_manifests')->whereIn('customer_id', $candidate->all())->pluck('customer_id'))
            ->merge(DB::table('booking_questionnaires')->whereIn('customer_id', $candidate->all())->pluck('customer_id'))
            ->merge(DB::table('question_answers')->whereIn('customer_id', $candidate->all())->pluck('customer_id'))
            // Parent-child relation inside customers table.
            ->merge(DB::table('customers')->whereIn('parent_id', $candidate->all())->pluck('parent_id'))
            ->filter(fn ($v) => !empty($v))
            ->map(fn ($v) => (int) $v)
            ->unique();

        $deletable = $candidate->diff($stillReferenced)->values();
        if ($deletable->isEmpty()) {
            return 0;
        }

        // Delete children first, then parents (FK on customers.parent_id).
        $deleted = 0;
        $childrenIds = DB::table('customers')
            ->whereIn('id', $deletable->all())
            ->whereNotNull('parent_id')
            ->pluck('id')
            ->all();

        if (!empty($childrenIds)) {
            $deleted += DB::table('customers')->whereIn('id', $childrenIds)->delete();
            $deletable = $deletable->diff($childrenIds)->values();
        }

        if ($deletable->isNotEmpty()) {
            $deleted += DB::table('customers')->whereIn('id', $deletable->all())->delete();
        }

        return (int) $deleted;
    }

    private function buildCounts(
        int $teamId,
        array $bookingIds,
        array $bookingItemIds,
        array $serviceIds,
        array $seasonIds,
        array $inventoryIds
    ): array {
        $counts = [];

        $counts['bookings'] = DB::table('bookings')->where('team_id', $teamId)->count();
        $counts['booking_items'] = DB::table('booking_items')->where('team_id', $teamId)->count();
        $counts['booking_inventories'] = DB::table('booking_inventories')->where('team_id', $teamId)->count();
        $counts['booking_fees'] = DB::table('booking_fees')->where('team_id', $teamId)->count();
        $counts['booking_payments'] = DB::table('booking_payments')->where('team_id', $teamId)->count();
        $counts['invoices'] = DB::table('invoices')->where('team_id', $teamId)->count();
        $counts['emails'] = DB::table('emails')->where('team_id', $teamId)->count();
        $counts['payment_gateway_logs'] = DB::table('payment_gateway_logs')->where('team_id', $teamId)->count();
        $counts['alerts'] = DB::table('alerts')->where('team_id', $teamId)->count();

        $counts['customer_manifests'] = DB::table('customer_manifests')->where('team_id', $teamId)->count();

        if (!empty($bookingItemIds)) {
            $counts['booking_questionnaires'] = DB::table('booking_questionnaires')->whereIn('booking_item_id', $bookingItemIds)->count();
            $counts['question_answers'] = DB::table('question_answers')->whereIn('booking_item_id', $bookingItemIds)->count();
            $counts['booking_items_customer'] = DB::table('booking_items_customer')->whereIn('booking_item_id', $bookingItemIds)->count();
            $counts['booking_staff'] = DB::table('booking_staff')->whereIn('booking_item_id', $bookingItemIds)->count();
            $counts['booking_details'] = DB::table('booking_details')->whereIn('booking_item_id', $bookingItemIds)->count();
            $counts['booking_infos'] = DB::table('booking_infos')->whereIn('booking_item_id', $bookingItemIds)->count();
        } else {
            $counts['booking_questionnaires'] = 0;
            $counts['question_answers'] = 0;
            $counts['booking_items_customer'] = 0;
            $counts['booking_staff'] = 0;
            $counts['booking_details'] = 0;
            $counts['booking_infos'] = 0;
        }

        $counts['services'] = DB::table('services')->where('team_id', $teamId)->count();
        $counts['seasons'] = DB::table('seasons')->where('team_id', $teamId)->count();
        $counts['inventories'] = DB::table('inventories')->where('team_id', $teamId)->count();
        $counts['inventory_time_slots'] = DB::table('inventory_time_slots')->where('team_id', $teamId)->count();
        $counts['inventory_type_days'] = DB::table('inventory_type_days')->where('team_id', $teamId)->count();
        $counts['inventory_slot_ranges'] = DB::table('inventory_slot_ranges')->where('team_id', $teamId)->count();
        $counts['inventory_overrides'] = DB::table('inventory_overrides')->where('team_id', $teamId)->count();
        $counts['closeouts'] = DB::table('closeouts')->where('team_id', $teamId)->count();
        $counts['closeout_day_time_slots'] = DB::table('closeout_day_time_slots')->where('team_id', $teamId)->count();
        $counts['promotions'] = DB::table('promotions')->where('team_id', $teamId)->count();
        $counts['vouchers'] = DB::table('vouchers')->where('team_id', $teamId)->count();
        $counts['discounts'] = DB::table('discounts')->where('team_id', $teamId)->count();
        $counts['wholesalers'] = DB::table('wholesalers')->where('team_id', $teamId)->count();
        $counts['wholesaler_rates'] = DB::table('wholesaler_rates')->where('team_id', $teamId)->count();
        $counts['wholesaler_bookings'] = empty($bookingIds)
            ? 0
            : DB::table('wholesaler_bookings')->whereIn('booking_id', $bookingIds)->count();

        $counts['questionnaire_answers'] = empty($bookingIds)
            ? 0
            : DB::table('questionnaire_answers')->whereIn('booking_id', $bookingIds)->count();

        // gift_voucher_items may have team_id, but can also be linked only via booking/service/season.
        $counts['gift_voucher_items'] = DB::table('gift_voucher_items')
            ->where('team_id', $teamId)
            ->when(!empty($bookingIds), fn ($q) => $q->orWhereIn('booking_id', $bookingIds))
            ->count();

        // Match the deletion selection (team operational files + any booking-linked files).
        $counts['files'] = DB::table('files')
            ->where(function ($q) use ($teamId, $bookingIds) {
                $q->where(function ($sub) use ($teamId) {
                    $sub->where('team_id', $teamId)
                        ->whereIn('type_file_id', [3, 4, 5, 6, 7]);
                });

                if (!empty($bookingIds)) {
                    $q->orWhereIn('booking_id', $bookingIds);
                }
            })
            ->count();

        return $counts;
    }

    private function printCounts(array $counts): void
    {
        $rows = collect($counts)->map(fn ($v, $k) => [$k, (string) $v])->values()->all();
        $this->table(['table', 'rows'], $rows);
    }
}
