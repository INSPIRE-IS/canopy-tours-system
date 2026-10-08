<?php

namespace App\Services\Calendar;

use App\Models\Service;
use App\Models\Season;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class PublicServiceAvailability
{
    public function __construct(private readonly PublicSeasonRangeResolver $rangeResolver)
    {
    }

    public function build(
        Service $service,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        int $adults = 0,
        int $children = 0
    ): array {
        $seasons = Season::query()
            ->with([
                'inventory.inventory_type_days',
                'inventory.inventory_time_slots',
            ])
            ->where('service_id', $service->id)
            ->where('team_id', $service->team_id)
            ->where('internal_only', false)
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->whereHas('inventory')
            ->get();

        $segments = $this->rangeResolver->resolve($seasons, $startDate, $endDate);
        $days = $this->emptyDays($startDate, $endDate);
        $slotsByDate = [];
        $totalRequested = $adults + $children;

        foreach ($segments as $segment) {
            $season = $segment['season'];
            $inventory = $season->inventory;
            $segmentStart = $segment['start_date'];
            $segmentEnd = $segment['end_date'];
            $bookingData = $this->bookingData(
                $inventory->id,
                $season->team_id,
                $segmentStart,
                $segmentEnd
            );

            $schedule = ServiceCalendarBuilder::build(
                $inventory,
                $season,
                $service,
                $bookingData,
                $segmentStart,
                $segmentEnd,
                $totalRequested,
                null,
                null,
                false
            );

            foreach ($schedule as $row) {
                foreach ($row['days'] ?? [] as $cell) {
                    $date = $cell['date'] ?? null;
                    if (!$date || !isset($days[$date]) || ($cell['status'] ?? null) !== 'available') {
                        continue;
                    }

                    $slot = [
                        'season_id' => $season->id,
                        'inventory_id' => $inventory->id,
                        'slot_id' => $row['slot_id'],
                        'time' => $row['time'],
                        'spaces_remaining' => max(0, (int) ($cell['slots'] ?? 0)),
                        'adult_price' => (float) $season->rake_rate_adult,
                        'child_price' => (float) $season->rake_rate_child,
                        'guide_price' => (float) $season->rake_rate_guide,
                    ];

                    $slotsByDate[$date][] = $slot;
                    $days[$date]['is_open'] = true;
                    $days[$date]['slot_count']++;
                    $days[$date]['from_price'] = $days[$date]['from_price'] === null
                        ? $slot['adult_price']
                        : min($days[$date]['from_price'], $slot['adult_price']);
                }
            }
        }

        foreach ($slotsByDate as &$slots) {
            usort($slots, fn (array $left, array $right) => strcmp($left['time'], $right['time']));
        }

        return [
            'service_id' => $service->id,
            'range' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'requested_pax' => [
                'adults' => $adults,
                'children' => $children,
            ],
            'days' => array_values($days),
            'slots_by_date' => $slotsByDate,
        ];
    }

    private function emptyDays(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $days = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateString = $date->toDateString();
            $days[$dateString] = [
                'date' => $dateString,
                'is_open' => false,
                'from_price' => null,
                'slot_count' => 0,
            ];
        }

        return $days;
    }

    private function bookingData(int $inventoryId, int $teamId, CarbonImmutable $startDate, CarbonImmutable $endDate)
    {
        return DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
            ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->where('items.inventory_id', $inventoryId)
            ->where('items.team_id', $teamId)
            ->whereNull('b.deleted_at')
            ->whereNull('items.deleted_at')
            ->whereNull('bi.deleted_at')
            ->whereBetween('bi.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'bi.date',
                'td.name as day',
                'its.start_time as time',
                DB::raw('SUM(bi.pax) as total'),
            ])
            ->groupBy('bi.date', 'td.name', 'its.start_time')
            ->get()
            ->keyBy(fn ($row) => $row->date . '_' . $row->time);
    }
}
