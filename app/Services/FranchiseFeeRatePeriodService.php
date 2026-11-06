<?php

namespace App\Services;

use App\Models\FranchiseFeeRatePeriod;
use Illuminate\Validation\ValidationException;

class FranchiseFeeRatePeriodService
{
    public function ensureNoOverlap(
        int $teamId,
        string $effectiveFrom,
        ?string $effectiveTo,
        ?int $excludePeriodId = null,
        bool $isActive = true,
    ): void {
        if (!$isActive) {
            return;
        }

        $query = FranchiseFeeRatePeriod::withoutGlobalScope('team_id')
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->when($excludePeriodId, fn ($query) => $query->where('id', '!=', $excludePeriodId))
            ->when($effectiveTo, fn ($query) => $query->whereDate('effective_from', '<=', $effectiveTo))
            ->where(function ($query) use ($effectiveFrom) {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $effectiveFrom);
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'This period overlaps another active franchise fee period.',
            ]);
        }
    }
}
