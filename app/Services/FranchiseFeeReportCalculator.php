<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class FranchiseFeeReportCalculator
{
    public function __construct(
        private readonly FranchiseFeeRateResolver $franchiseFeeRates,
    ) {
    }

    public function calculate(int $teamId, iterable $rowsByDate): array
    {
        $feePeriods = [];
        $totalInclVat = 0.0;

        foreach ($rowsByDate as $date => $row) {
            $amount = round((float) ($row['total'] ?? 0), 2);
            if ($amount == 0.0) {
                continue;
            }

            $totalInclVat += $amount;

            $feeRate = $this->franchiseFeeRates->forDate($teamId, $date);
            $feeKey = $feeRate['period_id'] === null
                ? 'fallback:'.$feeRate['royalty_percent'].':'.$feeRate['marketing_percent']
                : 'period:'.$feeRate['period_id'];

            if (!isset($feePeriods[$feeKey])) {
                $feePeriods[$feeKey] = [
                    ...$feeRate,
                    'activity_from' => $date,
                    'activity_to' => $date,
                    'total_incl_vat' => 0.0,
                    'royalty' => 0.0,
                    'marketing' => 0.0,
                ];
            }

            $feePeriods[$feeKey]['activity_from'] = min($feePeriods[$feeKey]['activity_from'], $date);
            $feePeriods[$feeKey]['activity_to'] = max($feePeriods[$feeKey]['activity_to'], $date);
            $feePeriods[$feeKey]['total_incl_vat'] += $amount;

        }

        foreach ($feePeriods as &$period) {
            $period['total_incl_vat'] = round($period['total_incl_vat'], 2);
            $period['royalty'] = round($period['total_incl_vat'] * ((float) $period['royalty_percent'] / 100), 2);
            $period['marketing'] = round($period['total_incl_vat'] * ((float) $period['marketing_percent'] / 100), 2);
        }
        unset($period);

        usort($feePeriods, fn (array $left, array $right) => $left['activity_from'] <=> $right['activity_from']);

        return [
            'total_incl_vat' => round($totalInclVat, 2),
            'royalty' => round(array_sum(array_column($feePeriods, 'royalty')), 2),
            'marketing' => round(array_sum(array_column($feePeriods, 'marketing')), 2),
            'fee_periods' => array_values($feePeriods),
        ];
    }

    public function periodDateLabel(array $period): string
    {
        $from = Carbon::parse($period['activity_from'])->format('d M Y');
        $to = Carbon::parse($period['activity_to'])->format('d M Y');

        return $from === $to ? $from : $from.' - '.$to;
    }
}
