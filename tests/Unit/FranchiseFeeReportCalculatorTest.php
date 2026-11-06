<?php

use App\Services\FranchiseFeeRateResolver;
use App\Services\FranchiseFeeReportCalculator;

it('calculates franchise fees using each activity dates applicable rates', function () {
    $franchiseRates = new class extends FranchiseFeeRateResolver
    {
        public function forDate(int $teamId, $date = null): array
        {
            $historical = (string) $date < '2026-11-01';

            return [
                'period_id' => $historical ? 1 : 2,
                'team_id' => $teamId,
                'royalty_percent' => $historical ? 10.0 : 9.0,
                'marketing_percent' => 1.5,
                'effective_from' => $historical ? '2020-01-01' : '2026-11-01',
                'effective_to' => $historical ? '2026-10-31' : null,
                'is_fallback' => false,
            ];
        }
    };
    $result = (new FranchiseFeeReportCalculator($franchiseRates))->calculate(3, [
        '2026-10-31' => ['total' => 1150.00],
        '2026-11-01' => ['total' => 1160.00],
    ]);

    expect($result['total_incl_vat'])->toBe(2310.0)
        ->and($result['royalty'])->toBe(219.4)
        ->and($result['marketing'])->toBe(34.65)
        ->and($result['fee_periods'])->toHaveCount(2)
        ->and($result['fee_periods'][0]['royalty'])->toBe(115.0)
        ->and($result['fee_periods'][1]['royalty'])->toBe(104.4);
});

it('ignores empty report dates when selecting financial periods', function () {
    $franchiseRates = new class extends FranchiseFeeRateResolver
    {
        public array $resolvedDates = [];

        public function forDate(int $teamId, $date = null): array
        {
            $this->resolvedDates[] = $date;

            return [
                'period_id' => 1,
                'team_id' => $teamId,
                'royalty_percent' => 10.0,
                'marketing_percent' => 1.5,
                'effective_from' => '2020-01-01',
                'effective_to' => null,
                'is_fallback' => false,
            ];
        }
    };
    $result = (new FranchiseFeeReportCalculator($franchiseRates))->calculate(3, [
        '2026-10-30' => ['total' => 0],
        '2026-10-31' => ['total' => 1150],
        '2026-11-01' => ['total' => 0],
    ]);

    expect($franchiseRates->resolvedDates)->toBe(['2026-10-31'])
        ->and($result['fee_periods'])->toHaveCount(1)
        ->and($result['royalty'])->toBe(115.0);
});
