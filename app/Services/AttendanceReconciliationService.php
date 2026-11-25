<?php

namespace App\Services;

use App\Models\CustomerManifest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceReconciliationService
{
    public function eligibleManifestQuery(
        int $teamId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Builder {
        return CustomerManifest::query()
            ->with([
                'customer',
                'booking_item.booking',
                'booking_item.booking_inventory' => fn ($inventory) => $inventory
                    ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->whereNull('deleted_at')
                    ->with('inventory_time_slot'),
            ])
            ->where('customer_manifests.team_id', $teamId)
            ->where('customer_manifests.is_indemnity', true)
            ->where('customer_manifests.attendance_status', CustomerManifest::ATTENDANCE_DEFAULT)
            ->whereNull('customer_manifests.deleted_at')
            ->whereHas('booking_item', function (Builder $bookingItem) use ($teamId, $from, $to) {
                $bookingItem
                    ->where('team_id', $teamId)
                    ->whereNull('deleted_at')
                    ->whereHas('booking', fn (Builder $booking) => $booking
                        ->where('team_id', $teamId)
                        ->whereNull('deleted_at')
                        ->operational())
                    ->whereHas('booking_inventory', fn (Builder $inventory) => $inventory
                        ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                        ->whereNull('deleted_at'));
            });
    }

    public function eligibleManifestIds(
        int $teamId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): Collection {
        return $this->eligibleManifestQuery($teamId, $from, $to)
            ->orderBy('customer_manifests.id')
            ->pluck('customer_manifests.id');
    }

    public function eligibleManifestCountsByDate(
        int $teamId,
        CarbonInterface $through,
    ): Collection {
        return DB::table('customer_manifests as manifests')
            ->join('booking_items as items', 'items.id', '=', 'manifests.booking_item_id')
            ->join('booking_inventories as inventories', 'inventories.booking_item_id', '=', 'items.id')
            ->join('bookings', 'bookings.id', '=', 'items.booking_id')
            ->where('manifests.team_id', $teamId)
            ->where('manifests.is_indemnity', true)
            ->where('manifests.attendance_status', CustomerManifest::ATTENDANCE_DEFAULT)
            ->whereNull('manifests.deleted_at')
            ->where('items.team_id', $teamId)
            ->whereNull('items.deleted_at')
            ->where('bookings.team_id', $teamId)
            ->whereNull('bookings.deleted_at')
            ->where(function ($query) {
                $query->whereNull('bookings.payment_window_expired_at')
                    ->orWhere('bookings.status_payment_id', 2);
            })
            ->whereNull('inventories.deleted_at')
            ->where('inventories.date', '<=', $through->toDateString())
            ->selectRaw('inventories.date as activity_date')
            ->selectRaw('COUNT(DISTINCT manifests.id) as candidate_count')
            ->groupBy('inventories.date')
            ->orderBy('inventories.date')
            ->get();
    }

    public function markArrived(Collection $manifestIds): int
    {
        if ($manifestIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($manifestIds) {
            $updated = 0;

            CustomerManifest::query()
                ->whereKey($manifestIds)
                ->lockForUpdate()
                ->get()
                ->each(function (CustomerManifest $manifest) use (&$updated) {
                    if ($manifest->attendance_status !== CustomerManifest::ATTENDANCE_DEFAULT) {
                        return;
                    }

                    $manifest->update(['attendance_status' => 'arrived']);
                    $updated++;
                });

            return $updated;
        });
    }
}
