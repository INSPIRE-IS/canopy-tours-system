<?php

use App\Exports\Reports\FranchiseFeesReportExport;

it('calculates royalty and marketing from the VAT-inclusive total', function () {
    $export = new class(3, null, null) extends FranchiseFeesReportExport
    {
        public function fees(float $totalInclVat, float $royaltyPercent, float $marketingPercent): array
        {
            return $this->calculateFranchiseFees($totalInclVat, $royaltyPercent, $marketingPercent);
        }
    };

    $fees = $export->fees(1150.00, 10.0, 1.5);

    expect($fees['royalty'])->toBe(115.0)
        ->and($fees['marketing'])->toBe(17.25);
});
