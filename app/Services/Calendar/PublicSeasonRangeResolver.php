<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use LogicException;

class PublicSeasonRangeResolver
{
    public function resolve(Collection $seasons, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $segments = $seasons
            ->filter(fn ($season) => $season->inventory !== null)
            ->filter(function ($season) use ($startDate, $endDate) {
                return CarbonImmutable::parse($season->start_date)->lte($endDate)
                    && CarbonImmutable::parse($season->end_date)->gte($startDate);
            })
            ->sortBy([
                ['start_date', 'asc'],
                ['end_date', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $previous = null;
        foreach ($segments as $season) {
            $seasonStart = CarbonImmutable::parse($season->start_date)->startOfDay();
            $seasonEnd = CarbonImmutable::parse($season->end_date)->startOfDay();

            if ($previous && $seasonStart->lte($previous['end'])) {
                throw new LogicException(sprintf(
                    'Public seasons %s and %s overlap for service %s.',
                    $previous['season']->id,
                    $season->id,
                    $season->service_id
                ));
            }

            $previous = [
                'season' => $season,
                'end' => $seasonEnd,
            ];
        }

        return $segments->map(function ($season) use ($startDate, $endDate) {
            $seasonStart = CarbonImmutable::parse($season->start_date)->startOfDay();
            $seasonEnd = CarbonImmutable::parse($season->end_date)->startOfDay();

            return [
                'season' => $season,
                'start_date' => $seasonStart->max($startDate),
                'end_date' => $seasonEnd->min($endDate),
            ];
        })->all();
    }
}
