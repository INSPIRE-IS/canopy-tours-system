<?php

namespace App\Services\Calendar;

use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class ServiceCalendarBuilder
{
    public static function build($inventory, $season, $service, $bookingData, $startDate, $endDate, $totalRequested, $startTime = null)
    {

        $lockoutHours = $inventory->slot_cutoff_hours ?? 0; // 0 = no lockout, 3 = 3h, 4 = 4h, etc
        if (auth()->user() && $inventory->allow_internal_override) {
            $lockoutHours = 0;
        }
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
        $calendar = self::initCalendar($season, $service, $bookingData, $startDate, $endDate, $totalRequested, $allowedDays, $timeSlots, $lockoutHours);

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

    protected static function initCalendar($season, $service, $bookingData, $startDate, $endDate, $totalRequested, $allowedDays, $timeSlots, $lockoutHours)
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
                // time period in hours before a slot closes
                $slotCloseTime = $lockoutHours ?? 0;

                $now = now('Africa/Johannesburg');
                Log::info('now' . $now);
                $cutoff = $slotCloseTime > 0
                    ? $now->copy()->addHours($slotCloseTime)
                    : null;
                // $cutoff = now()->addDay(); // 24-hour lockout    
                Log::info('cutoff' . $cutoff);

                // Not an allowed weekday
                if (!in_array($dayOfWeek, $allowedDays)) {
                    $row['days'][$label] = [
                        'date' => $dateStr,
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Not an allowed day',
                    ];
                    continue;
                }
                // Rule: Past date
                if ($date->isBefore(now()->startOfDay())) {
                    $row['days'][$label] = [
                        'date'   => $dateStr,
                        'slots'  => 0,
                        'status' => 'unavailable',
                        'note'   => 'Past date',
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
}
