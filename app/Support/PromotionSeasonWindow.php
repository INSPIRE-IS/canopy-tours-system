<?php

namespace App\Support;

final class PromotionSeasonWindow
{
    public static function validationErrors(
        iterable $seasons,
        string $type,
        $seasonId,
        string $activityStartDate,
        string $activityEndDate,
        bool $internalUseOnly
    ): array {
        $eligibleSeasons = collect($seasons)->filter(function ($season) use ($internalUseOnly) {
            return $internalUseOnly || ! (bool) self::value($season, 'internal_only');
        });

        if ($type === 'time_slot') {
            $selectedSeason = $eligibleSeasons->first(function ($season) use ($seasonId) {
                return (string) self::value($season, 'id') === (string) $seasonId;
            });

            if (! $selectedSeason
                || ! self::containsDate($selectedSeason, $activityStartDate)
                || ! self::containsDate($selectedSeason, $activityEndDate)) {
                return [
                    'activity_start_date' => ['Activity dates must fall within the selected season.'],
                ];
            }

            return [];
        }

        $errors = [];
        if (! $eligibleSeasons->contains(fn ($season) => self::containsDate($season, $activityStartDate))) {
            $errors['activity_start_date'] = ['Activity start date must fall within an eligible season for the selected service.'];
        }
        if (! $eligibleSeasons->contains(fn ($season) => self::containsDate($season, $activityEndDate))) {
            $errors['activity_end_date'] = ['Activity end date must fall within an eligible season for the selected service.'];
        }

        return $errors;
    }

    private static function containsDate($season, string $date): bool
    {
        $start = self::normalizeDate(self::value($season, 'start_date'));
        $end = self::normalizeDate(self::value($season, 'end_date'));
        $date = self::normalizeDate($date);

        return $start !== '' && $end !== '' && $date >= $start && $date <= $end;
    }

    private static function normalizeDate($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return substr((string) $value, 0, 10);
    }

    private static function value($season, string $key)
    {
        return is_array($season) ? ($season[$key] ?? null) : ($season->{$key} ?? null);
    }
}
