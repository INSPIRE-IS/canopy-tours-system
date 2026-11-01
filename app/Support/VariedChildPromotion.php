<?php

namespace App\Support;

final class VariedChildPromotion
{
    public const MODE_ALL_CHILDREN = 'all_children';

    public const MODE_ONE_PER_ADULT = 'one_per_adult';

    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED_VALUE = 'fixed_value';

    public static function normalizeParams(array $params): array
    {
        if (! empty($params['tiers'])) {
            return $params;
        }

        $minAdults = $params['min_adults'] ?? null;
        if (! is_numeric($minAdults) || (int) $minAdults != (float) $minAdults || (int) $minAdults < 1) {
            throw new \InvalidArgumentException('Varied child promotions require a minimum adult count of at least 1.');
        }

        $mode = (string) ($params['child_discount_mode'] ?? self::MODE_ALL_CHILDREN);
        if (! in_array($mode, [self::MODE_ALL_CHILDREN, self::MODE_ONE_PER_ADULT], true)) {
            throw new \InvalidArgumentException('Varied child discount rule is invalid.');
        }

        $type = (string) ($params['child_discount_type'] ?? self::TYPE_PERCENT);
        if (! in_array($type, [self::TYPE_PERCENT, self::TYPE_FIXED_VALUE], true)) {
            throw new \InvalidArgumentException('Varied child discount type must be % or R.');
        }

        $value = $params['child_discount_value'] ?? $params['child_discount_percent'] ?? null;
        if ($value === null || $value === '' || ! is_numeric($value) || (float) $value < 0) {
            throw new \InvalidArgumentException('Varied child promotions require a non-negative discount value.');
        }
        if ($type === self::TYPE_PERCENT && (float) $value > 100) {
            throw new \InvalidArgumentException('Varied child discount percent cannot exceed 100.');
        }

        $params['min_adults'] = (int) $minAdults;
        $params['child_discount_mode'] = $mode;
        $params['child_discount_type'] = $type;
        $params['child_discount_value'] = (float) $value;
        if ($type === self::TYPE_PERCENT) {
            $params['child_discount_percent'] = (float) $value;
        } else {
            unset($params['child_discount_percent']);
        }

        return $params;
    }

    public static function discountedChildCount(int $adults, int $children, array $params): int
    {
        $params = self::normalizeParams($params);
        if (! empty($params['tiers'])) {
            return 0;
        }

        $minAdults = (int) $params['min_adults'];
        $mode = $params['child_discount_mode'];

        if ($mode === self::MODE_ONE_PER_ADULT) {
            if ($adults < $minAdults || $children < 1) {
                throw new \InvalidArgumentException(
                    "This promo requires at least {$minAdults} adult".($minAdults === 1 ? '' : 's').' and one child.'
                );
            }

            return min($adults, $children);
        }

        return $adults >= $minAdults ? $children : 0;
    }

    public static function pricingBreakdown(float $baseRate, int $adults, int $children, array $params): array
    {
        $params = self::normalizeParams($params);
        $discountedCount = self::discountedChildCount($adults, $children, $params);
        $rackCount = max(0, $children - $discountedCount);
        $value = (float) $params['child_discount_value'];
        $discountedRate = $params['child_discount_type'] === self::TYPE_FIXED_VALUE
            ? max(0, $baseRate - $value)
            : max(0, $baseRate - ($baseRate * ($value / 100)));
        $lineTotal = round(($baseRate * $rackCount) + ($discountedRate * $discountedCount), 2);

        return [
            'rack_count' => $rackCount,
            'discounted_count' => $discountedCount,
            'discounted_rate' => round($discountedRate, 2),
            'line_total' => $lineTotal,
            'average_rate' => $children > 0 ? round($lineTotal / $children, 2) : $baseRate,
        ];
    }
}
