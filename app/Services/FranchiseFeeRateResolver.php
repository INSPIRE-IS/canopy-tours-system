<?php

namespace App\Services;

use App\Models\FranchiseFeeRatePeriod;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class FranchiseFeeRateResolver
{
    public const FALLBACK_ROYALTY_PERCENT = 10.0;

    public const FALLBACK_MARKETING_PERCENT = 1.5;

    public function forDate(int $teamId, CarbonInterface|string|null $date = null): array
    {
        $effectiveDate = $date instanceof CarbonInterface
            ? $date->toDateString()
            : Carbon::parse($date ?? now())->toDateString();

        $period = FranchiseFeeRatePeriod::withoutGlobalScope('team_id')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->where(function ($query) use ($effectiveDate) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveDate);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        return [
            'period_id' => $period?->id,
            'team_id' => $teamId,
            'royalty_percent' => $period === null
                ? self::FALLBACK_ROYALTY_PERCENT
                : (float) $period->royalty_percent,
            'marketing_percent' => $period === null
                ? self::FALLBACK_MARKETING_PERCENT
                : (float) $period->marketing_percent,
            'effective_from' => $period?->effective_from?->toDateString(),
            'effective_to' => $period?->effective_to?->toDateString(),
            'is_fallback' => $period === null,
        ];
    }
}
