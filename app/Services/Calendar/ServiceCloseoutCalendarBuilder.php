<?php

namespace App\Services\Calendar;

use App\Models\Closeout;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ServiceCloseoutCalendarBuilder
{
    public static function apply(
        array $calendar,
        $inventory,
        int $serviceId,
        int $seasonId,
        array $allowedDays,          // not used here; kept for signature stability
        $timeSlotsModels,            // full collection of inventory_time_slots (with ids)
        $startDate,
        $endDate
    ): array {
        // 1) Fetch closeouts for the service/season
        $closeouts = Closeout::with('closeout_day_time_slots')
            ->where('service_id', $serviceId)
            ->where('season_id', $seasonId)
            ->get();

        // 2) Build map of date => 'ALL' or date => [time_slot_id => true]
        $blockedSlots = [];

        foreach ($closeouts as $c) {
            $start = Carbon::parse($c->start_date);
            $end   = Carbon::parse($c->end_date);

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $dateStr   = $date->toDateString();
                $dayOfWeek = $date->dayOfWeekIso; // 1..7

                if ($c->closeout_day_time_slots->isEmpty()) {
                    $blockedSlots[$dateStr] = 'ALL';
                } else {
                    foreach ($c->closeout_day_time_slots as $slot) {
                        if ((int)$slot->day_id === $dayOfWeek) {
                            $blockedSlots[$dateStr][$slot->time_slot_id] = true;
                        }
                    }
                }
            }
        }

        if (empty($blockedSlots)) {
            return $calendar; // nothing to apply
        }

        // 3) Build start_time -> timeslot model (for id lookup)
        $slotByStart = $inventory->inventory_time_slots->keyBy('start_time');

        // 4) Mutate existing calendar cells
        foreach ($calendar as &$row) {
            $timeStr   = $row['time']; // e.g. "10:00:00"
            $slotModel = $slotByStart->get($timeStr);
            $slotId    = $slotModel ? $slotModel->id : null;

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $label   = $date->format('D d');   // matches initCalendar labels
                $dateStr = $date->toDateString();

                if (!isset($row['days'][$label])) {
                    continue; // safety
                }

                // Date overrides are deliberate exceptions and must win over closeouts.
                // This lets staff reopen a public-holiday/group slot without editing a
                // long-running closeout period.
                if (!empty($row['days'][$label]['is_date_override'])) {
                    continue;
                }

                // Don't override "unavailable" (not an allowed day)
                if (($row['days'][$label]['status'] ?? null) === 'unavailable') {
                    continue;
                }

                // Apply closeouts
                if (!isset($blockedSlots[$dateStr])) {
                    continue;
                }

                // Full day closeout
                if ($blockedSlots[$dateStr] === 'ALL') {
                    $row['days'][$label] = [
                        'date'   => $dateStr,
                        'slots'  => 0,
                        'status' => 'blocked_day',
                        'note'   => 'Blocked whole day',
                    ];
                    continue;
                }

                // Slot-level closeout (only if we know the slot id)
                if ($slotId && isset($blockedSlots[$dateStr][$slotId])) {
                    $row['days'][$label] = [
                        'date'   => $dateStr,
                        'slots'  => 0,
                        'status' => 'blocked_slot',
                        'note'   => 'Blocked slot',
                    ];
                }
            }
        }
        unset($row); // break reference

        return $calendar;
    }
}
