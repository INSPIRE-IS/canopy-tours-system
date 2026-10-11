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
        int $children = 0,
        ?string $excludeHoldSessionId = null
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
        $arrivalMinutes = $this->arrivalMinutes($service->pillbox_conditions);

        foreach ($segments as $segment) {
            $season = $segment['season'];
            $inventory = $season->inventory;
            $segmentStart = $segment['start_date'];
            $segmentEnd = $segment['end_date'];
            $bookingData = $this->bookingData(
                $inventory->id,
                $season->team_id,
                $segmentStart,
                $segmentEnd,
                $excludeHoldSessionId
            );

            // The shared builder keys cells by labels such as "Wed 03". Build one
            // calendar month at a time so equal labels in adjacent months cannot collide.
            foreach ($this->monthRanges($segmentStart, $segmentEnd) as [$rangeStart, $rangeEnd]) {
                $schedule = ServiceCalendarBuilder::build(
                    $inventory,
                    $season,
                    $service,
                    $bookingData,
                    $rangeStart,
                    $rangeEnd,
                    $totalRequested,
                    null,
                    null,
                    false
                );

                foreach ($schedule as $row) {
                    foreach ($row['days'] ?? [] as $cell) {
                        $date = $cell['date'] ?? null;
                        if (!$date || !isset($days[$date])) {
                            continue;
                        }

                        $status = $cell['status'] ?? 'unavailable';
                        if ($status === 'full' && $days[$date]['status'] !== 'available') {
                            $days[$date]['status'] = 'full';
                        }

                        if ($status !== 'available') {
                            continue;
                        }

                        $slot = [
                            'season_id' => $season->id,
                            'inventory_id' => $inventory->id,
                            'slot_id' => $row['slot_id'],
                            'time' => $row['time'],
                            'arrival_by' => $this->arrivalBy($row['time'], $arrivalMinutes),
                            'spaces_remaining' => max(0, (int) ($cell['slots'] ?? 0)),
                            'adult_price' => (float) $season->rake_rate_adult,
                            'child_price' => (float) $season->rake_rate_child,
                            'guide_price' => (float) $season->rake_rate_guide,
                        ];

                        $slotsByDate[$date][] = $slot;
                        $days[$date]['is_open'] = true;
                        $days[$date]['status'] = 'available';
                        $days[$date]['slot_count']++;
                        $days[$date]['from_price'] = $days[$date]['from_price'] === null
                            ? $slot['adult_price']
                            : min($days[$date]['from_price'], $slot['adult_price']);
                    }
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
                'status' => 'unavailable',
                'from_price' => null,
                'slot_count' => 0,
            ];
        }

        return $days;
    }

    private function arrivalMinutes(?string $conditions): ?int
    {
        if (!$conditions || !preg_match('/arrive\s+(\d+)\s+minutes?/i', $conditions, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function monthRanges(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $ranges = [];
        $cursor = $startDate;

        while ($cursor->lte($endDate)) {
            $rangeEnd = $cursor->endOfMonth()->min($endDate)->startOfDay();
            $ranges[] = [$cursor, $rangeEnd];
            $cursor = $rangeEnd->addDay()->startOfDay();
        }

        return $ranges;
    }

    private function arrivalBy(string $time, ?int $arrivalMinutes): ?string
    {
        if ($arrivalMinutes === null) {
            return null;
        }

        return CarbonImmutable::createFromFormat('H:i:s', $time, 'Africa/Johannesburg')
            ->subMinutes($arrivalMinutes)
            ->format('H:i');
    }

    private function bookingData(
        int $inventoryId,
        int $teamId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?string $excludeHoldSessionId = null
    )
    {
        $bookings = DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
            ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->where('items.inventory_id', $inventoryId)
            ->where('items.team_id', $teamId)
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
                'td.name as day',
                'its.start_time as time',
                DB::raw('SUM(bi.pax) as total'),
            ])
            ->groupBy('bi.date', 'td.name', 'its.start_time')
            ->get()
            ->keyBy(fn ($row) => $row->date . '_' . $row->time);

        $holds = DB::table('public_booking_holds as holds')
            ->join('inventory_time_slots as hold_slots', 'hold_slots.id', '=', 'holds.inventory_time_slot_id')
            ->where('holds.inventory_id', $inventoryId)
            ->where('holds.team_id', $teamId)
            ->whereNull('holds.completed_at')
            ->whereNull('holds.cancelled_at')
            ->where('holds.expires_at', '>', now())
            ->whereBetween('holds.booking_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(
                $excludeHoldSessionId,
                fn ($query) => $query->where('holds.session_id', '!=', $excludeHoldSessionId)
            )
            ->select([
                'holds.booking_date as date',
                'hold_slots.start_time as time',
                DB::raw('SUM(holds.adults + holds.children) as total'),
            ])
            ->groupBy('holds.booking_date', 'hold_slots.start_time')
            ->get();

        foreach ($holds as $hold) {
            $key = $hold->date . '_' . $hold->time;

            if ($bookings->has($key)) {
                $bookings[$key]->total = (int) $bookings[$key]->total + (int) $hold->total;
                continue;
            }

            $bookings->put($key, $hold);
        }

        return $bookings;
    }
}
