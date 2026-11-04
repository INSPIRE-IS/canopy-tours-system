<?php

namespace App\Exports\Reports;

use App\Models\BookingItem;
use App\Models\Discount;
use App\Models\LookupItem;
use App\Models\Wholesaler;
use App\Models\WholesalerRate;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FranchiseFeesReportExport implements FromCollection, WithHeadings, ShouldAutoSize, ShouldQueue, WithEvents
{
    private array $discountNames = [];
    private array $wholesalerNames = [];
    private array $tradeExchangeWholesalers = [];
    private array $prepaidVoucherRateIds = [];
    private array $prepaidVoucherRateKeys = [];
    private ?int $reportNotesRow = null;

    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly array $serviceIds = [],
    ) {
    }

    public function collection(): Collection
    {
        $items = $this->bookingItems();
        $this->loadSupportingData($items);

        $rowsByDate = $this->initialRows($items);

        foreach ($items as $item) {
            $date = $this->activityDate($item);
            if (!$date) {
                continue;
            }

            $row = $rowsByDate[$date] ?? $this->emptyDay($date);
            $this->applyBookingItem($row, $item);
            $rowsByDate[$date] = $row;
        }

        ksort($rowsByDate);

        $runningTotal = 0.0;
        $columnTotals = $this->emptyDay('Column Totals');
        $rows = collect();

        foreach ($rowsByDate as $row) {
            $runningTotal += $row['total'];
            $this->addColumnTotals($columnTotals, $row);
            $rows->push($this->formatRow($row, $runningTotal));
        }

        $totalPax = $columnTotals['pax'];
        $totalInclVat = $columnTotals['total'];

        $rows->push($this->formatTotalsRow($columnTotals, $runningTotal));
        $rows->push($this->blankRow());
        $rows->push($this->summaryRow('Total Pax', (string) $totalPax));
        $rows->push($this->summaryRow('Total incl VAT', $this->money($totalInclVat)));
        $vat = round($totalInclVat - ($totalInclVat / 1.15), 2);
        $turnoverExclVat = $totalInclVat - $vat;
        $rows->push($this->summaryRow('VAT', $this->money($vat)));
        $rows->push($this->summaryRow('Turnover excl VAT', $this->money($turnoverExclVat)));
        $rows->push($this->summaryRow('Average Per Guest', $totalPax > 0 ? $this->money($totalInclVat / $totalPax) : $this->money(0)));
        $reportSettings = LookupItem::franchiseFeeSettingsForTeam($this->teamId);
        $royaltyPercent = (float) ($reportSettings['franchise_royalty_percent'] ?? 10.0);
        $marketingPercent = (float) ($reportSettings['franchise_marketing_percent'] ?? 1.5);
        $franchiseFees = $this->calculateFranchiseFees($totalInclVat, $royaltyPercent, $marketingPercent);
        $rows->push($this->summaryRow("Royalty @ {$this->percentLabel($royaltyPercent)}", $this->money($franchiseFees['royalty'])));
        $rows->push($this->summaryRow("Marketing Account @ {$this->percentLabel($marketingPercent)}", $this->money($franchiseFees['marketing'])));
        $rows->push($this->blankRow());
        $this->reportNotesRow = $rows->count() + 2;
        $rows->push($this->summaryRow('Report Notes', 'V1 buckets known 10/15/20/25%, configured trade-exchange wholesalers, prepaid voucher partner rates, complimentary, and full-rate rows; unmapped promos/discounts/non-trade wholesalers/custom rates are grouped under Other with structured pricing comments only.'));

        return $rows;
    }

    protected function calculateFranchiseFees(
        float $totalInclVat,
        float $royaltyPercent,
        float $marketingPercent,
    ): array {
        return [
            'royalty' => $totalInclVat * ($royaltyPercent / 100),
            'marketing' => $totalInclVat * ($marketingPercent / 100),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if (!$this->reportNotesRow) {
                    return;
                }

                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                $noteRange = "D{$this->reportNotesRow}:{$lastColumn}{$this->reportNotesRow}";
                $event->sheet->mergeCells($noteRange);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(14);
                $event->sheet->getDelegate()->getRowDimension($this->reportNotesRow)->setRowHeight(48);
                $event->sheet->getStyle($noteRange)->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }

    public function headings(): array
    {
        return [
            'Date',
            'No of Pax',
            'Average Rate',
            'Total',
            'Cumulative Total',
            'Full Rate Pax',
            'Full Rate Income',
            'Complimentary Pax',
            'Trade Exchange Pax',
            'Trade Exchange Income',
            'Voucher Partner Pax',
            'Voucher Partner Income',
            '10% Pax',
            '10% Income',
            '15% Pax',
            '15% Income',
            '20% Pax',
            '20% Income',
            '25% Pax',
            '25% Income',
            'Other Pax',
            'Other Income',
            'TOTAL',
            'Special Comments',
        ];
    }

    private function bookingItems(): Collection
    {
        $query = BookingItem::query()
            ->select('booking_items.*')
            ->with([
                'booking:id,reference,lead_name,deleted_at',
                'booking.booking_discounts.booking_discount_items.discount:id,name,percent_off',
                'booking_detail:id,booking_item_id,is_override_time_slots',
                'booking_fee:id,booking_item_id,tot_amount',
                'booking_inventory:id,booking_item_id,date,inventory_override_id',
                'booking_inventory.inventory_override:id,note',
                'price_lines:id,booking_item_id,source_type,label,meta',
                'promotion:id,name,promo_code,type,value',
                'wholesaler_rate:id,wholesaler_id,service_id,season_id,is_prepaid_voucher',
                'service:id,name',
                'season:id,rake_rate_adult,rake_rate_child,rake_rate_guide',
            ])
            ->where('team_id', $this->teamId)
            ->whereHas('booking', fn(Builder $query) => $query
                ->whereNull('deleted_at')
                ->operational());

        if (!empty($this->serviceIds)) {
            $query->whereIn('service_id', $this->serviceIds);
        }

        if ($this->startDate || $this->endDate) {
            $query->whereHas('booking_inventory', function (Builder $query) {
                if ($this->startDate) {
                    $query->whereDate('date', '>=', $this->startDate);
                }
                if ($this->endDate) {
                    $query->whereDate('date', '<=', $this->endDate);
                }
            });
        }

        return $query->get();
    }

    private function loadSupportingData(Collection $items): void
    {
        $this->discountNames = Discount::select('id', 'name')->where('team_id', $this->teamId)->pluck('name', 'id')->toArray();
        $wholesalers = Wholesaler::select('id', 'name', 'is_trade_exchange')
            ->where('team_id', $this->teamId)
            ->get();
        $this->wholesalerNames = $wholesalers->pluck('name', 'id')->toArray();
        $this->tradeExchangeWholesalers = $wholesalers
            ->filter(fn(Wholesaler $wholesaler) => (bool) $wholesaler->is_trade_exchange)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $voucherRates = WholesalerRate::select('id', 'wholesaler_id', 'service_id', 'season_id')
            ->where('team_id', $this->teamId)
            ->where('is_prepaid_voucher', true)
            ->get();
        $this->prepaidVoucherRateIds = $voucherRates->pluck('id')->map(fn($id) => (int) $id)->all();
        $this->prepaidVoucherRateKeys = $voucherRates
            ->mapWithKeys(fn(WholesalerRate $rate) => [
                $this->wholesalerRateKey((int) $rate->wholesaler_id, (int) $rate->service_id, (int) $rate->season_id) => true,
            ])
            ->all();
    }

    private function initialRows(Collection $items): array
    {
        $rows = [];

        if ($this->startDate && $this->endDate) {
            $date = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            while ($date->lte($end)) {
                $key = $date->toDateString();
                $rows[$key] = $this->emptyDay($key);
                $date->addDay();
            }

            return $rows;
        }

        foreach ($items as $item) {
            $date = $this->activityDate($item);
            if ($date) {
                $rows[$date] = $this->emptyDay($date);
            }
        }

        return $rows;
    }

    private function applyBookingItem(array &$row, BookingItem $item): void
    {
        $total = round((float) ($item->booking_fee?->tot_amount ?? 0), 2);
        $pax = $this->paxTotal($item);

        $row['pax'] += $pax;
        $row['total'] += $total;

        if (!empty($item->wholesaler_id)) {
            $wholesalerName = $this->wholesalerNames[$item->wholesaler_id] ?? 'Wholesaler rate';
            if (in_array((int) $item->wholesaler_id, $this->tradeExchangeWholesalers, true)) {
                $this->addBucket($row, 'trade_exchange', $pax, $total, $wholesalerName);
                return;
            }

            $bucket = $this->isPrepaidVoucherPartner($item) ? 'voucher_partner' : 'other';

            $this->addBucket($row, $bucket, $pax, $total, $wholesalerName);
            return;
        }

        if ($pax > 0 && $total <= 0.01) {
            $this->addBucket($row, 'complimentary', $pax, $total, $this->otherComment($item));
            return;
        }

        $discountItems = $item->booking?->booking_discounts
            ? $item->booking->booking_discounts
                ->filter(fn($discount) => empty($discount->booking_item_id) || (int) $discount->booking_item_id === (int) $item->id)
                ->flatMap(fn($discount) => $discount->booking_discount_items ?? collect())
            : collect();

        if ($discountItems->isNotEmpty()) {
            $this->applyDiscountBooking($row, $item, $discountItems);
            return;
        }

        if (!empty($item->promotion_id)) {
            $promotion = $item->promotion;
            $label = trim(($promotion?->name ?? 'Promotion') . ' ' . ($promotion?->promo_code ? '(' . $promotion->promo_code . ')' : ''));
            $this->addBucket($row, $this->promoBucket($promotion?->type, $promotion?->value), $pax, $total, $label);
            return;
        }

        if ($this->hasCustomRate($item)) {
            $this->addBucket($row, 'other', $pax, $total, $this->otherComment($item));
            return;
        }

        $this->addBucket($row, 'full', $pax, $total, null);
    }

    private function applyDiscountBooking(array &$row, BookingItem $item, Collection $discountItems): void
    {
        $coveredCounts = ['adult' => 0, 'child' => 0, 'guide' => 0];

        foreach ($discountItems as $discountItem) {
            $count = (int) ($discountItem->count ?? 0);
            $rate = (float) ($discountItem->final_rate ?? 0);
            $type = $discountItem->pax_type ?? 'adult';

            $coveredCounts[$type] = ($coveredCounts[$type] ?? 0) + $count;

            $name = $discountItem->discount?->name
                ?? $this->discountNames[$discountItem->discount_id] ?? 'Discount';
            $this->addBucket($row, $this->discountBucket($discountItem), $count, $count * $rate, $name);
        }

        foreach (['adult', 'child', 'guide'] as $type) {
            $remaining = max(0, $this->paxCountForType($item, $type) - ($coveredCounts[$type] ?? 0));
            if ($remaining <= 0) {
                continue;
            }

            $rate = $this->rateForType($item, $type);
            $income = $remaining * $rate;
            $bucket = $this->rateMatchesRack($item, $type, $rate) ? 'full' : 'other';
            $this->addBucket($row, $bucket, $remaining, $income, $bucket === 'other' ? $this->otherComment($item) : null);
        }
    }

    private function discountBucket(object $discountItem): string
    {
        $rate = (float) ($discountItem->final_rate ?? 0);
        if ($rate <= 0.01) {
            return 'complimentary';
        }

        return $this->percentBucket((float) ($discountItem->discount?->percent_off ?? 0));
    }

    private function promoBucket(?string $type, mixed $value): string
    {
        if ($type === 'percent') {
            return $this->percentBucket((float) $value);
        }

        return 'other';
    }

    private function isPrepaidVoucherPartner(BookingItem $item): bool
    {
        if (!$item->wholesaler_id) {
            return false;
        }

        if ($item->wholesaler_rate_id && in_array((int) $item->wholesaler_rate_id, $this->prepaidVoucherRateIds, true)) {
            return true;
        }

        if ($item->relationLoaded('wholesaler_rate') && (bool) ($item->wholesaler_rate?->is_prepaid_voucher ?? false)) {
            return true;
        }

        return !empty($this->prepaidVoucherRateKeys[$this->wholesalerRateKey(
            (int) $item->wholesaler_id,
            (int) $item->service_id,
            (int) $item->season_id
        )]);
    }

    private function wholesalerRateKey(int $wholesalerId, int $serviceId, int $seasonId): string
    {
        return "{$wholesalerId}:{$serviceId}:{$seasonId}";
    }

    private function percentBucket(float $percent): string
    {
        foreach ([10, 15, 20, 25] as $knownPercent) {
            if (abs($percent - $knownPercent) < 0.01) {
                return 'discount_' . $knownPercent;
            }
        }

        return 'other';
    }

    private function addBucket(array &$row, string $bucket, int $pax, float $income, ?string $comment): void
    {
        if ($pax <= 0 && round($income, 2) == 0.0) {
            return;
        }

        $row[$bucket . '_pax'] += $pax;
        $row[$bucket . '_income'] += round($income, 2);

        if ($comment) {
            $row['comments'][$bucket][] = $comment;
        }
    }

    private function hasCustomRate(BookingItem $item): bool
    {
        if ($item->booking_inventory?->inventory_override_id) {
            return true;
        }

        foreach (['adult', 'child', 'guide'] as $type) {
            if ($this->paxCountForType($item, $type) > 0 && !$this->rateMatchesRack($item, $type, $this->rateForType($item, $type))) {
                return true;
            }
        }

        return false;
    }

    private function otherComment(BookingItem $item): string
    {
        $comments = [];

        $overrideReasons = $item->price_lines
            ? $item->price_lines
                ->where('source_type', 'override')
                ->pluck('meta')
                ->map(fn($meta) => is_array($meta) ? trim((string) ($meta['override_reason'] ?? '')) : '')
                ->filter()
                ->values()
                ->all()
            : [];

        foreach ($overrideReasons as $reason) {
            $comments[] = $reason;
        }

        $overrideNote = trim((string) ($item->booking_inventory?->inventory_override?->note ?? ''));
        if ($overrideNote !== '') {
            $comments[] = $overrideNote;
        }

        return implode(', ', array_values(array_unique(array_filter($comments)))) ?: 'Custom rate - no report reason captured';
    }

    private function rateMatchesRack(BookingItem $item, string $type, float $rate): bool
    {
        return abs($rate - $this->rackRateForType($item, $type)) < 0.01;
    }

    private function rackRateForType(BookingItem $item, string $type): float
    {
        $season = $item->season;

        return match ($type) {
            'adult' => (float) ($season?->price_adult ?? $season?->rake_rate_adult ?? 0),
            'child' => (float) ($season?->price_child ?? $season?->rake_rate_child ?? 0),
            'guide' => (float) ($season?->price_guide ?? $season?->rake_rate_guide ?? 0),
            default => 0.0,
        };
    }

    private function rateForType(BookingItem $item, string $type): float
    {
        return match ($type) {
            'adult' => (float) ($item->price_adult ?? 0),
            'child' => (float) ($item->price_child ?? 0),
            'guide' => (float) ($item->price_guide ?? 0),
            default => 0.0,
        };
    }

    private function paxTotal(BookingItem $item): int
    {
        return (int) ($item->pax_adults ?? 0)
            + (int) ($item->pax_children ?? 0)
            + (int) ($item->pax_guides ?? 0);
    }

    private function paxCountForType(BookingItem $item, string $type): int
    {
        return match ($type) {
            'adult' => (int) ($item->pax_adults ?? 0),
            'child' => (int) ($item->pax_children ?? 0),
            'guide' => (int) ($item->pax_guides ?? 0),
            default => 0,
        };
    }

    private function activityDate(BookingItem $item): ?string
    {
        $raw = $item->booking_inventory?->getRawOriginal('date') ?? $item->booking_inventory?->date;

        return $raw ? Carbon::parse($raw)->toDateString() : null;
    }

    private function emptyDay(string $date): array
    {
        return [
            'date' => $date,
            'pax' => 0,
            'total' => 0.0,
            'full_pax' => 0,
            'full_income' => 0.0,
            'complimentary_pax' => 0,
            'complimentary_income' => 0.0,
            'trade_exchange_pax' => 0,
            'trade_exchange_income' => 0.0,
            'voucher_partner_pax' => 0,
            'voucher_partner_income' => 0.0,
            'discount_10_pax' => 0,
            'discount_10_income' => 0.0,
            'discount_15_pax' => 0,
            'discount_15_income' => 0.0,
            'discount_20_pax' => 0,
            'discount_20_income' => 0.0,
            'discount_25_pax' => 0,
            'discount_25_income' => 0.0,
            'other_pax' => 0,
            'other_income' => 0.0,
            'comments' => [],
        ];
    }

    private function formatRow(array $row, float $runningTotal): array
    {
        return [
            $row['date'],
            $row['pax'] ?: '',
            $row['pax'] > 0 ? $this->money($row['total'] / $row['pax']) : '',
            $row['total'] > 0 ? $this->money($row['total']) : '',
            $this->money($runningTotal),
            $row['full_pax'] ?: '',
            $this->money($row['full_income']),
            $row['complimentary_pax'] ?: '',
            $row['trade_exchange_pax'] ?: '',
            $this->money($row['trade_exchange_income']),
            $row['voucher_partner_pax'] ?: '',
            $this->money($row['voucher_partner_income']),
            $row['discount_10_pax'] ?: '',
            $this->money($row['discount_10_income']),
            $row['discount_15_pax'] ?: '',
            $this->money($row['discount_15_income']),
            $row['discount_20_pax'] ?: '',
            $this->money($row['discount_20_income']),
            $row['discount_25_pax'] ?: '',
            $this->money($row['discount_25_income']),
            $row['other_pax'] ?: '',
            $this->money($row['other_income']),
            $this->money($row['total']),
            $this->formatComments($row['comments']),
        ];
    }

    private function formatComments(array $comments): string
    {
        $groups = [];

        foreach ($this->commentLabels() as $bucket => $label) {
            $values = $comments[$bucket] ?? [];
            if (!is_array($values)) {
                $values = [$values];
            }

            $values = array_values(array_unique(array_filter(array_map(
                fn($value) => trim((string) $value),
                $values
            ))));

            if (!empty($values)) {
                $groups[] = $label . ': ' . implode(', ', $values);
            }
        }

        return implode('; ', $groups);
    }

    private function commentLabels(): array
    {
        return [
            'full' => 'Full Rate',
            'complimentary' => 'Complimentary',
            'trade_exchange' => 'Trade Exchange',
            'voucher_partner' => 'Voucher Partner',
            'discount_10' => '10%',
            'discount_15' => '15%',
            'discount_20' => '20%',
            'discount_25' => '25%',
            'other' => 'Other',
        ];
    }

    private function formatTotalsRow(array $row, float $runningTotal): array
    {
        return [
            'Column Totals',
            $row['pax'] ?: '',
            $row['pax'] > 0 ? $this->money($row['total'] / $row['pax']) : '',
            $this->money($row['total']),
            $this->money($runningTotal),
            $row['full_pax'] ?: '',
            $this->money($row['full_income']),
            $row['complimentary_pax'] ?: '',
            $row['trade_exchange_pax'] ?: '',
            $this->money($row['trade_exchange_income']),
            $row['voucher_partner_pax'] ?: '',
            $this->money($row['voucher_partner_income']),
            $row['discount_10_pax'] ?: '',
            $this->money($row['discount_10_income']),
            $row['discount_15_pax'] ?: '',
            $this->money($row['discount_15_income']),
            $row['discount_20_pax'] ?: '',
            $this->money($row['discount_20_income']),
            $row['discount_25_pax'] ?: '',
            $this->money($row['discount_25_income']),
            $row['other_pax'] ?: '',
            $this->money($row['other_income']),
            $this->money($row['total']),
            '',
        ];
    }

    private function addColumnTotals(array &$totals, array $row): void
    {
        foreach ([
            'pax',
            'total',
            'full_pax',
            'full_income',
            'complimentary_pax',
            'complimentary_income',
            'trade_exchange_pax',
            'trade_exchange_income',
            'voucher_partner_pax',
            'voucher_partner_income',
            'discount_10_pax',
            'discount_10_income',
            'discount_15_pax',
            'discount_15_income',
            'discount_20_pax',
            'discount_20_income',
            'discount_25_pax',
            'discount_25_income',
            'other_pax',
            'other_income',
        ] as $key) {
            $totals[$key] += $row[$key];
        }
    }

    private function summaryRow(string $label, string $value): array
    {
        $row = $this->blankRow();
        $row[0] = $label;
        $row[3] = $value;

        return $row;
    }

    private function blankRow(): array
    {
        return array_fill(0, count($this->headings()), '');
    }

    private function money(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    private function percentLabel(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . '%';
    }
}
