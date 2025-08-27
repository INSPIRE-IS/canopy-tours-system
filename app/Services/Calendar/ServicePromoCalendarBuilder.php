<?php

namespace App\Services\Calendar;

use App\Models\Promotion;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ServicePromoCalendarBuilder
{
    public static function apply(array $calendar, int $serviceId): array
    {
        $today = Carbon::today();
        $promos = Promotion::with('promo_day_time_slots')
            ->where('service_id', $serviceId)
            ->where('booking_start_date', '<=', $today)
            ->where('booking_end_date', '>=', $today)
            ->where('internal_use_only', false) // skip internal promos for frontend
            ->get();
        //dd($promos);
        if ($promos->isEmpty()) {
            return $calendar;
        }

        foreach ($promos as $p) {
            $start = Carbon::parse($p->activity_start_date);
            $end   = Carbon::parse($p->activity_end_date);

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $dateStr   = $date->toDateString();   // "2025-08-26"
                $dayLabel  = $date->format('D d');    // "Tue 26"
                $dayOfWeek = $date->dayOfWeekIso;     // 1..7

                foreach ($calendar as &$row) {
                    if (!isset($row['days'][$dayLabel])) {
                        continue;
                    }

                    // whole day promo
                    if ($p->promo_day_time_slots->isEmpty()) {
                        $row['days'][$dayLabel]['promo'] = [
                            'promo_id' => $p->id,
                            'type'     => $p->type,
                            'value'    => $p->value,
                        ];
                        continue;
                    }

                    // slot-level promo (match day + timeslot_id)
                    foreach ($p->promo_day_time_slots as $slot) {
                        if ((int) $slot->day_id === $dayOfWeek && $row['slot_id'] === $slot->time_slot_id) {
                            // check if this row corresponds to the timeslot
                            if (($row['slot_id'] ?? null) === $slot->time_slot_id) {
                                $row['days'][$dayLabel]['promo'] = [
                                    'promo_id' => $p->id,
                                    'type'     => $p->type,
                                    'value'    => $p->value,
                                ];
                            }
                        }
                    }
                }
                unset($row);
            }
        }

        return $calendar;
    }
}
