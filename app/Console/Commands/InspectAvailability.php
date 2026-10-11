<?php

namespace App\Console\Commands;

use App\Models\Closeout;
use App\Models\Inventory;
use App\Models\InventorySlotRange;
use App\Models\Season;
use App\Models\Service;
use App\Services\Calendar\ServiceCalendarBuilder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InspectAvailability extends Command
{
    protected $signature = 'availability:inspect
        {--service= : Service id}
        {--season= : Season id}
        {--from= : Start date YYYY-MM-DD}
        {--to= : End date YYYY-MM-DD}
        {--team= : Optional team id, defaults to service team}
        {--export : Write the result to storage/app/reports}
        {--path= : Optional storage-relative CSV path, e.g. reports/availability.csv}';

    // php artisan availability:inspect --service=21 --season=19 --from=2026-07-18 --to=2026-07-24
    // php artisan availability:inspect --service=21 --season=19 --from=2026-07-18 --to=2026-07-24 --export
    // php artisan availability:inspect --service=21 --season=19 --from=2026-07-18 --to=2026-07-24 --export --path=reports/canopy-july-availability.csv

    protected $description = 'Inspect effective availability, closeouts, date overrides, bookings, and capacity for a service/season/date range.';

    public function handle(): int
    {
        $serviceId = (int) $this->option('service');
        $seasonId = (int) $this->option('season');
        $from = $this->option('from');
        $to = $this->option('to');

        if (!$serviceId || !$seasonId || !$from || !$to) {
            $this->error('Required: --service=ID --season=ID --from=YYYY-MM-DD --to=YYYY-MM-DD');
            return self::FAILURE;
        }

        $startDate = Carbon::parse($from)->startOfDay();
        $endDate = Carbon::parse($to)->startOfDay();
        if ($startDate->gt($endDate)) {
            $this->error('--from must be before or equal to --to.');
            return self::FAILURE;
        }

        $service = Service::findOrFail($serviceId);
        $season = Season::findOrFail($seasonId);
        if ((int) $season->service_id !== (int) $service->id) {
            $this->error('The selected season does not belong to the selected service.');
            return self::FAILURE;
        }

        $inventory = Inventory::with(['inventory_type_days', 'inventory_time_slots'])
            ->where('service_id', $service->id)
            ->where('season_id', $season->id)
            ->first();

        if (!$inventory) {
            $this->error('No inventory found for this service/season.');
            return self::FAILURE;
        }

        $teamId = $this->option('team') ? (int) $this->option('team') : (int) ($service->team_id ?? $season->team_id ?? 0);
        $bookingData = $this->bookingData($inventory->id, $teamId, $startDate, $endDate);
        $calendar = ServiceCalendarBuilder::build(
            $inventory,
            $season,
            $service,
            $bookingData,
            $startDate,
            $endDate,
            0,
            null,
            null,
            true
        );
        $actualMap = $this->actualCalendarMap($calendar);

        $overrideDates = $this->overrideDates($inventory->id, $startDate, $endDate);
        $overrideMap = $this->overrideMap($inventory->id, $startDate, $endDate);
        $closeoutMap = $this->closeoutMap($service->id, $season->id, $startDate, $endDate);

        $rows = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateStr = $date->toDateString();
            foreach ($inventory->inventory_time_slots->sortBy('start_time') as $slot) {
                $override = $overrideMap[$dateStr][$slot->id] ?? null;
                $closeout = $closeoutMap[$dateStr][$slot->id] ?? $closeoutMap[$dateStr]['ALL'] ?? null;
                $booked = (int) ($bookingData[$dateStr . '_' . $slot->start_time]->total ?? 0);
                $actual = $actualMap[$dateStr][$slot->id] ?? null;

                $expectedStatus = $this->expectedStatus(
                    isset($overrideDates[$dateStr]),
                    $override,
                    $closeout,
                    (bool) $slot->is_override_only
                );

                $rows[] = [
                    'date' => $dateStr,
                    'day' => $date->format('D'),
                    'time' => $slot->start_time,
                    'slot_id' => $slot->id,
                    'override_only_slot' => $slot->is_override_only ? 'yes' : 'no',
                    'date_override_active' => isset($overrideDates[$dateStr]) ? 'yes' : 'no',
                    'override_id' => $override['id'] ?? '',
                    'override_internal_only' => isset($override) ? ($override['internal_only'] ? 'yes' : 'no') : '',
                    'override_capacity' => $override['capacity_override'] ?? '',
                    'override_note' => $override['note'] ?? '',
                    'closeout_id' => $closeout['id'] ?? '',
                    'closeout_name' => $closeout['name'] ?? '',
                    'closeout_reason' => $closeout['reason'] ?? '',
                    'booked_pax' => $booked,
                    'effective_capacity' => $override['capacity_override'] ?? $service->capacity,
                    'expected_status' => $expectedStatus,
                    'actual_status' => $actual['status'] ?? '',
                    'actual_note' => $actual['note'] ?? '',
                    'actual_slots' => $actual['slots'] ?? '',
                    'actual_internal_only' => isset($actual['internal_only']) ? ($actual['internal_only'] ? 'yes' : 'no') : '',
                ];
            }
        }

        $this->info("Availability inspection: {$service->name} / {$season->name} / {$startDate->toDateString()} to {$endDate->toDateString()}");
        $this->table([
            'date',
            'day',
            'time',
            'date_override_active',
            'override_id',
            'internal',
            'cap',
            'closeout_id',
            'booked',
            'expected',
            'actual',
            'slots',
        ], collect($rows)->map(fn($row) => [
            $row['date'],
            $row['day'],
            $row['time'],
            $row['date_override_active'],
            $row['override_id'],
            $row['override_internal_only'],
            $row['override_capacity'],
            $row['closeout_id'],
            $row['booked_pax'],
            $row['expected_status'],
            $row['actual_status'],
            $row['actual_slots'],
        ])->all());

        if ($this->option('export')) {
            $path = $this->option('path') ?: 'reports/availability-inspection-' . now()->format('Ymd-His') . '.csv';
            $this->writeCsv($path, $rows);
            $this->info('CSV written: storage/app/' . $path);
        }

        return self::SUCCESS;
    }

    private function bookingData(int $inventoryId, int $teamId, Carbon $startDate, Carbon $endDate)
    {
        return DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->where('items.inventory_id', $inventoryId)
            ->when($teamId, fn($query) => $query->where('items.team_id', $teamId))
            ->whereNull('b.deleted_at')
            ->where(function ($query) {
                $query->whereNull('b.payment_window_expired_at')
                    ->orWhere('b.status_payment_id', 2);
            })
            ->whereNull('items.deleted_at')
            ->whereNull('bi.deleted_at')
            ->whereBetween('bi.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'bi.date',
                'its.start_time as time',
                DB::raw('SUM(bi.pax) as total'),
            ])
            ->groupBy('bi.date', 'its.start_time')
            ->get()
            ->keyBy(fn($row) => $row->date . '_' . $row->time);
    }

    private function actualCalendarMap(array $calendar): array
    {
        $map = [];
        foreach ($calendar as $row) {
            foreach (($row['days'] ?? []) as $cell) {
                $date = $cell['date'] ?? null;
                $slotId = $row['slot_id'] ?? null;
                if ($date && $slotId) {
                    $map[$date][$slotId] = $cell;
                }
            }
        }

        return $map;
    }

    private function overrideDates(int $inventoryId, Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        $ranges = InventorySlotRange::where('inventory_id', $inventoryId)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        foreach ($ranges as $range) {
            foreach (CarbonPeriod::create($range->start_date, $range->end_date) as $date) {
                $dates[$date->toDateString()] = true;
            }
        }

        return $dates;
    }

    private function overrideMap(int $inventoryId, Carbon $startDate, Carbon $endDate): array
    {
        $map = [];
        $ranges = InventorySlotRange::where('inventory_id', $inventoryId)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        foreach ($ranges as $range) {
            foreach (CarbonPeriod::create($range->start_date, $range->end_date) as $date) {
                $map[$date->toDateString()][$range->time_slot_id] = [
                    'id' => $range->id,
                    'internal_only' => (bool) $range->internal_only,
                    'capacity_override' => $range->capacity_override,
                    'note' => $range->note,
                ];
            }
        }

        return $map;
    }

    private function closeoutMap(int $serviceId, int $seasonId, Carbon $startDate, Carbon $endDate): array
    {
        $map = [];
        $closeouts = Closeout::with('closeout_day_time_slots')
            ->where('service_id', $serviceId)
            ->where('season_id', $seasonId)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        foreach ($closeouts as $closeout) {
            foreach (CarbonPeriod::create($closeout->start_date, $closeout->end_date) as $date) {
                $dateStr = $date->toDateString();
                $dayId = $date->dayOfWeekIso;
                $payload = [
                    'id' => $closeout->id,
                    'name' => $closeout->name,
                    'reason' => $closeout->reason,
                ];

                if ($closeout->closeout_day_time_slots->isEmpty()) {
                    $map[$dateStr]['ALL'] = $payload;
                    continue;
                }

                foreach ($closeout->closeout_day_time_slots as $slot) {
                    if ((int) $slot->day_id === $dayId) {
                        $map[$dateStr][$slot->time_slot_id] = $payload;
                    }
                }
            }
        }

        return $map;
    }

    private function expectedStatus(bool $overrideDateActive, ?array $override, ?array $closeout, bool $overrideOnlySlot): string
    {
        if ($override) {
            return 'available_by_date_override';
        }

        if ($overrideDateActive) {
            return 'not_available_not_in_date_override';
        }

        if ($closeout) {
            return 'closed_by_closeout';
        }

        if ($overrideOnlySlot) {
            return 'not_available_override_only_slot';
        }

        return 'available_base_inventory';
    }

    private function writeCsv(string $path, array $rows): void
    {
        Storage::makeDirectory(dirname($path));
        $handle = fopen(Storage::path($path), 'w');
        if (!$handle) {
            throw new \RuntimeException('Unable to write CSV: ' . $path);
        }

        if (!empty($rows)) {
            fputcsv($handle, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }
}
