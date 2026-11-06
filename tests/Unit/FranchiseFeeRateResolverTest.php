<?php

use App\Services\FranchiseFeeRatePeriodService;
use App\Services\FranchiseFeeRateResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->originalDatabaseConnection = config('database.default');
    config([
        'database.default' => 'franchise_rates_test',
        'database.connections.franchise_rates_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
    ]);
    DB::purge('franchise_rates_test');

    Schema::connection('franchise_rates_test')->create('franchise_fee_rate_periods', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('team_id');
        $table->decimal('royalty_percent', 5, 2);
        $table->decimal('marketing_percent', 5, 2);
        $table->date('effective_from');
        $table->date('effective_to')->nullable();
        $table->boolean('is_active')->default(true);
        $table->text('note')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () {
    DB::purge('franchise_rates_test');
    config(['database.default' => $this->originalDatabaseConnection]);
});

it('resolves franchise percentages by activity date', function () {
    $firstPeriodId = insertFranchiseRatePeriodForResolverTest(3, 10, 1.5, '2020-01-01', '2026-10-31');
    $secondPeriodId = insertFranchiseRatePeriodForResolverTest(3, 9, 1.5, '2026-11-01');

    $resolver = app(FranchiseFeeRateResolver::class);

    expect($resolver->forDate(3, '2026-10-31'))
        ->period_id->toBe($firstPeriodId)
        ->royalty_percent->toBe(10.0)
        ->marketing_percent->toBe(1.5)
        ->and($resolver->forDate(3, '2026-11-01'))
        ->period_id->toBe($secondPeriodId)
        ->royalty_percent->toBe(9.0)
        ->marketing_percent->toBe(1.5);
});

it('does not use another sites franchise period', function () {
    insertFranchiseRatePeriodForResolverTest(4, 7, 2, '2020-01-01');

    expect(app(FranchiseFeeRateResolver::class)->forDate(3, '2026-11-01'))
        ->period_id->toBeNull()
        ->royalty_percent->toBe(10.0)
        ->marketing_percent->toBe(1.5)
        ->is_fallback->toBeTrue();
});

it('allows adjacent active franchise periods without treating them as overlapping', function () {
    insertFranchiseRatePeriodForResolverTest(3, 10, 1.5, '2020-01-01', '2026-10-31');

    app(FranchiseFeeRatePeriodService::class)
        ->ensureNoOverlap(3, '2026-11-01', null);

    expect(true)->toBeTrue();
});

it('rejects an active franchise period that overlaps an existing boundary', function () {
    insertFranchiseRatePeriodForResolverTest(3, 10, 1.5, '2020-01-01', '2026-10-31');

    expect(fn () => app(FranchiseFeeRatePeriodService::class)
        ->ensureNoOverlap(3, '2026-10-31', '2027-12-31'))
        ->toThrow(ValidationException::class, 'overlaps another active franchise fee period');
});

it('allows overlapping inactive franchise periods', function () {
    insertFranchiseRatePeriodForResolverTest(3, 10, 1.5, '2020-01-01');

    app(FranchiseFeeRatePeriodService::class)
        ->ensureNoOverlap(3, '2026-11-01', null, null, false);

    expect(true)->toBeTrue();
});

function insertFranchiseRatePeriodForResolverTest(
    int $teamId,
    float $royaltyPercent,
    float $marketingPercent,
    string $effectiveFrom,
    ?string $effectiveTo = null,
    bool $isActive = true,
): int {
    return DB::table('franchise_fee_rate_periods')->insertGetId([
        'team_id' => $teamId,
        'royalty_percent' => $royaltyPercent,
        'marketing_percent' => $marketingPercent,
        'effective_from' => $effectiveFrom,
        'effective_to' => $effectiveTo,
        'is_active' => $isActive,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
