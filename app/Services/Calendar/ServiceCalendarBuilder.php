<?php

namespace App\Services\Calendar;

use Carbon\CarbonPeriod;

class ServiceCalendarBuilder
{
    public static function build($inventory, $season, $service, $bookingData, $startDate, $endDate, $totalRequested, $startTime = null)
    {

        $serviceId = $inventory->service_id;
        $seasonId = $inventory->season_id;
        $allowedDays = $inventory->inventory_type_days->pluck('type_day_id')->toArray();

        $timeSlots = $inventory->inventory_time_slots
            ->when($startTime, function ($collection, $startTime) {
                // filter the collection by start_time
                return $collection->where('id', $startTime);
            })
            //->pluck('start_time')
            ->unique()->sort();
        logger()->info("$serviceId, $seasonId, $startDate, $endDate");
        $calendar = self::initCalendar($season, $service, $bookingData, $startDate, $endDate, $totalRequested, $allowedDays, $timeSlots);

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

        return $calendar;
    }

    protected static function initCalendar($season, $service, $bookingData, $startDate, $endDate, $totalRequested, $allowedDays, $timeSlots)
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

                if (!in_array($dayOfWeek, $allowedDays)) {
                    $row['days'][$label] = [
                        'date' => $dateStr,
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Not an allowed day',
                    ];
                    continue;
                }
                // once we have the booking data, we can calculate the remaining slots
                if (true) {
                    $dateTimeStr = $dateStr . '_' . $timeSlot;
                    $booked = $bookingData[$dateTimeStr]->total ?? 0;
                    $remaining = $service->capacity - $booked;
                    if ($remaining < $totalRequested) {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'status' => 'full',
                            'note' => 'Fully booked',
                        ];
                    } else {
                        $row['days'][$label] = [
                            'date' => $dateStr,
                            'slots' => $remaining,
                            'status' => 'available',
                            'rake_rate_adult' => $season->rake_rate_adult,
                        ];
                    }
                }
            }

            $calendar[] = $row;
        }
        return $calendar;
    }
}
