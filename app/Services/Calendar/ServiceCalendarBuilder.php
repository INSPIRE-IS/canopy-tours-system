<?php

namespace App\Services\Calendar;

use Carbon\CarbonPeriod;
use App\Models\InventorySlotRange;

class ServiceCalendarBuilder
{
    public static function build($inventory, $season, $service, $bookingData, $startDate, $endDate, $totalRequested, $startTime = null, $currentBooking = null, $includeInternalOverrides = null)
    {
        if ($includeInternalOverrides === null) {
            $includeInternalOverrides = auth()->check();
        }

        $lockoutHours = $inventory->slot_cutoff_hours ?? 0; // 0 = no lockout, 3 = 3h, 4 = 4h, etc
        if (auth()->user() && $inventory->allow_internal_override) {
            $lockoutHours = 0;
        }
        $serviceId = $inventory->service_id;
        $seasonId = $inventory->season_id;
        $allowedDays = $inventory->inventory_type_days->pluck('type_day_id')->toArray();

        $overrideMap = self::buildOverrideMap($inventory->id, $startDate, $endDate, $includeInternalOverrides);
        $overrideSlotIds = collect($overrideMap)->map(function ($slots) {
            return is_array($slots) ? array_keys($slots) : [];
        })->flatten()->unique()->values()->all();
        $timeSlots = $inventory->inventory_time_slots
            ->filter(function ($slot) use ($overrideSlotIds) {
                return !$slot->is_override_only || in_array($slot->id, $overrideSlotIds, true);
            })
            ->when($startTime, function ($collection, $startTime) {
                // filter the collection by start_time
                return $collection->where('id', $startTime);
            })
            ->unique('id')
            ->sortBy(fn($slot) => (string) $slot->start_time)
            ->values();
        $calendar = self::initCalendar($season, $service, $bookingData, $startDate, $endDate, $totalRequested, $allowedDays, $timeSlots, $lockoutHours, $currentBooking, $overrideMap);

        // Apply Closeouts
        $calendar = ServiceCloseoutCalendarBuilder::apply(
            $calendar,
            $inventory,
            $serviceId,
            $seasonId,
            $allowedDays, // not used here, but kept to avoid signature change elsewhere
            $inventory->inventory_time_slots, // pass full models for id lookup
            $startDate,
            $endDate
        );
        //dd($calendar);
        // Apply Promotions
        $calendar = ServicePromoCalendarBuilder::apply($calendar, $serviceId);

        // 3) Funnel slots LAST
        // $calendar = self::applySlotFunneling($calendar, 2); // 2 = number of open slots allowed
        if ($inventory->slot_funneling > 0) {
            $calendar = self::applySlotFunneling(
                $calendar,
                $inventory->slot_funneling
            );
        }

        return $calendar;
    }

    protected static function initCalendar($season, $service, $bookingData, $startDate, $endDate, $totalRequested, $allowedDays, $timeSlots, $lockoutHours, $currentBooking = null, $overrideMap = [])
    {
        $calendar = [];
        foreach ($timeSlots as $timeSlot) {
            //dd($timeSlots);
            $row = [
                'time'     => $timeSlot->start_time,  // keep old shape for frontend
                'slot_id'  => $timeSlot->id,          // NEW: internal reference
                'days'     => []
            ];


            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $label = $date->format('D d');
                $dayOfWeek = $date->dayOfWeekIso;
                $dateStr = $date->toDateString();
                $overrideSlots = $overrideMap[$dateStr] ?? [];
                $hasSlotOverride = array_key_exists($timeSlot->id, $overrideSlots);
                $overrideMeta = $hasSlotOverride ? ($overrideSlots[$timeSlot->id] ?? null) : null;
                // time period in hours before a slot closes
                $slotCloseTime = $lockoutHours ?? 0;

                $now = now('Africa/Johannesburg');
                $cutoff = $slotCloseTime > 0
                    ? $now->copy()->addHours($slotCloseTime)
                    : null;

                if ($date->lt($season->start_date) || $date->gt($season->end_date)) {
                    $row['days'][$label] = [
                        'date' => $dateStr,
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Outside season',
                        'internal_only' => false,
                        'is_date_override' => false,
                    ];
                    continue;
                }

                $slotCapacity = (int) ($service->capacity ?? 0);
                if ($hasSlotOverride) {
                    $overrideCapacity = is_array($overrideMeta) ? ($overrideMeta['capacity_override'] ?? null) : null;
                    if ($overrideCapacity !== null) {
                        $slotCapacity = (int) $overrideCapacity;
                    }
                } elseif (!empty($timeSlot->is_override_only)) {
                    $row['days'][$label] = [
                        'date' => $dateStr,
                        'slots' => 0,
                            'status' => 'unavailable',
                            'note' => 'Override-only slot',
                            'internal_only' => false,
                            'is_date_override' => false,
                        ];
                        continue;
                    } else {
                    // Not an allowed weekday
                    if (!in_array($dayOfWeek, $allowedDays)) {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => 0,
                            'status' => 'unavailable',
                            'note' => 'Not an allowed day',
                            'internal_only' => false,
                            'is_date_override' => false,
                        ];
                        continue;
                    }
                }
                // Rule: Past date
                if ($dateStr < now('Africa/Johannesburg')->toDateString()) {
                    $row['days'][$label] = [
                        'date'   => $dateStr,
                        'slots'  => 0,
                        'status' => 'unavailable',
                        'note'   => 'Past date',
                        'internal_only' => false,
                        'is_date_override' => (bool) $overrideMeta,
                    ];
                    continue;
                }
                // Rule: Online booking closes 24h before start time
                // $slotDateTime = $date
                //     ->copy()
                //     ->timezone('Africa/Johannesburg')
                //     ->setTimeFromTimeString($timeSlot->start_time);
                $slotDateTime = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $dateStr . ' ' . $timeSlot->start_time,
                    'Africa/Johannesburg'
                );
                if ($cutoff && $slotDateTime->lt($cutoff)) {
                    $row['days'][$label] = [
                        'date'   => $dateStr,
                        'slots'  => 0,
                        'status' => 'unavailable', // closed
                        'note'   => 'Online booking closes' . $cutoff . 'h before tour',
                        'internal_only' => (bool) (is_array($overrideMeta) ? ($overrideMeta['internal_only'] ?? false) : false),
                        'is_date_override' => (bool) $overrideMeta,
                    ];
                    continue;
                }

                // once we have the booking data, we can calculate the remaining slots
                if (true) {
                    $dateTimeStr = $dateStr . '_' . $timeSlot->start_time;
                    $booked = $bookingData[$dateTimeStr]->total ?? 0;
                    $remaining = $slotCapacity - $booked;
                    $effectiveRemaining = $remaining;
                    if (is_array($currentBooking)) {
                        $currentDate = $currentBooking['date'] ?? null;
                        $currentTime = $currentBooking['time'] ?? null;
                        $currentPax = (int) ($currentBooking['pax'] ?? 0);
                        if ($currentDate === $dateStr && $currentTime === $timeSlot->start_time) {
                            $effectiveRemaining = $remaining + $currentPax;
                        }
                    }
                    if ($effectiveRemaining <= 0) {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'capacity' => $slotCapacity,
                            'status' => 'full',
                            'note' => 'Fully booked',
                            'internal_only' => (bool) (is_array($overrideMeta) ? ($overrideMeta['internal_only'] ?? false) : false),
                            'is_date_override' => (bool) $overrideMeta,
                        ];
                    } elseif ($effectiveRemaining < $totalRequested) {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'capacity' => $slotCapacity,
                            'status' => 'full',
                            'note' => 'Fully booked',
                            'internal_only' => (bool) (is_array($overrideMeta) ? ($overrideMeta['internal_only'] ?? false) : false),
                            'is_date_override' => (bool) $overrideMeta,
                        ];
                    } else {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'capacity' => $slotCapacity,
                            'status' => 'available',
                            'rake_rate_adult' => $season->rake_rate_adult,
                            'internal_only' => (bool) (is_array($overrideMeta) ? ($overrideMeta['internal_only'] ?? false) : false),
                            'is_date_override' => (bool) $overrideMeta,
                        ];
                    }
                }
            }

            $calendar[] = $row;
        }
        return $calendar;
    }

    protected static function applySlotFunneling($calendar, $maxOpen = 2)
    {
        // calendar is an array of rows (each row = a timeslot)
        // we need to reorganize it per day first
        $days = [];

        foreach ($calendar as $row) {
            foreach ($row['days'] as $label => $info) {
                $days[$label][] = [
                    'slot_id'  => $row['slot_id'],
                    'time'     => $row['time'],
                    'info'     => $info,
                ];
            }
        }

        // Apply funnel logic per day
        foreach ($days as $label => &$daySlots) {
            $openCount = 0;

            // Sort by chronological order (just in case)
            usort(
                $daySlots,
                fn($a, $b) =>
                strtotime($a['time']) <=> strtotime($b['time'])
            );

            foreach ($daySlots as &$slot) {
                // If the slot is full, it's always "allowed"
                if ($slot['info']['status'] === 'full') {
                    continue;
                }

                // If we already opened enough slots, close this one
                if ($openCount >= $maxOpen) {
                    $slot['info']['status'] = 'unavailable';
                    $slot['info']['note'] = 'Later slots locked until earlier slots fill';
                    continue;
                }

                // This slot is open
                $openCount++;
            }
        }

        // Rebuild calendar back into original shape
        $newCalendar = [];
        foreach ($calendar as $row) {
            $newRow = $row;
            foreach ($newRow['days'] as $label => &$info) {
                foreach ($days[$label] as $slotData) {
                    if ($slotData['slot_id'] == $newRow['slot_id']) {
                        $info = $slotData['info'];
                    }
                }
            }
            $newCalendar[] = $newRow;
        }

        return $newCalendar;
    }

    protected static function buildOverrideMap(int $inventoryId, $startDate, $endDate, bool $includeInternalOverrides): array
    {
        $ranges = InventorySlotRange::where('inventory_id', $inventoryId)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->when(!$includeInternalOverrides, function ($query) {
                $query->where('internal_only', false);
            })
            ->get();

        if ($ranges->isEmpty()) {
            return [];
        }

        $map = [];
        foreach ($ranges as $range) {
            foreach (CarbonPeriod::create($range->start_date, $range->end_date) as $date) {
                $dateStr = $date->toDateString();
                if (!isset($map[$dateStr])) {
                    $map[$dateStr] = [];
                }
                $map[$dateStr][$range->time_slot_id] = [
                    'capacity_override' => $range->capacity_override,
                    'internal_only' => (bool) $range->internal_only,
                ];
            }
        }

        return $map;
    }
}
