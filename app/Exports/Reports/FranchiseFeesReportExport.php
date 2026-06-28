<?php

namespace App\Exports\Reports;

use App\Models\BookingItem;
use App\Models\BookingNote;
use App\Models\Discount;
use App\Models\Wholesaler;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FranchiseFeesReportExport implements FromCollection, WithHeadings, ShouldAutoSize, ShouldQueue
{
    private array $bookingNotesByItem = [];
    private array $discountNames = [];
    private array $wholesalerNames = [];

    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
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
        $totalPax = 0;
        $totalInclVat = 0.0;
        $rows = collect();

        foreach ($rowsByDate as $row) {
            $runningTotal += $row['total'];
            $totalPax += $row['pax'];
            $totalInclVat += $row['total'];
            $rows->push($this->formatRow($row, $runningTotal));
        }

        $rows->push($this->blankRow());
        $rows->push($this->summaryRow('Total Pax', (string) $totalPax));
        $rows->push($this->summaryRow('Total incl VAT', $this->money($totalInclVat)));
        $vat = round($totalInclVat - ($totalInclVat / 1.15), 2);
        $rows->push($this->summaryRow('VAT', $this->money($vat)));
        $rows->push($this->summaryRow('Turnover excl VAT', $this->money($totalInclVat - $vat)));
        $rows->push($this->summaryRow('Average Per Guest', $totalPax > 0 ? $this->money($totalInclVat / $totalPax) : $this->money(0)));
        $rows->push($this->summaryRow('Royalty @ 10%', $this->money($totalInclVat * 0.10)));
        $rows->push($this->summaryRow('Marketing Account', 'Confirm percentage per franchise'));

        return $rows;
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
            'Wholesaler Pax',
            'Wholesaler Income',
            'Promotion Pax',
            'Promotion Income',
            'Discount Pax',
            'Discount Income',
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
                'booking.booking_discounts.booking_discount_items.discount:id,name',
                'booking_detail:id,booking_item_id,is_override_time_slots',
                'booking_fee:id,booking_item_id,tot_amount',
                'booking_inventory:id,booking_item_id,date,inventory_override_id',
                'booking_inventory.inventory_override:id,note',
                'promotion:id,name,promo_code,type',
                'service:id,name',
                'season:id,rake_rate_adult,rake_rate_child,rake_rate_guide',
            ])
            ->where('team_id', $this->teamId)
            ->whereHas('booking', fn(Builder $query) => $query->whereNull('deleted_at'));

        if ($this->serviceId) {
            $query->where('service_id', $this->serviceId);
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
        $bookingItemIds = $items->pluck('id')->all();
        $this->bookingNotesByItem = empty($bookingItemIds)
            ? []
            : BookingNote::query()
                ->whereIn('booking_item_id', $bookingItemIds)
                ->whereIn('type', ['booking_note', 'invoice_note'])
                ->orderBy('created_at')
                ->get()
                ->groupBy('booking_item_id')
                ->map(fn(Collection $notes) => $notes->pluck('note')->filter()->unique()->values()->all())
                ->toArray();

        $this->discountNames = Discount::select('id', 'name')->pluck('name', 'id')->toArray();
        $this->wholesalerNames = Wholesaler::select('id', 'name')->pluck('name', 'id')->toArray();
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

        $discountItems = $item->booking?->booking_discounts
            ? $item->booking->booking_discounts->flatMap(fn($discount) => $discount->booking_discount_items ?? collect())
            : collect();

        if ($discountItems->isNotEmpty()) {
            $this->applyDiscountBooking($row, $item, $discountItems);
            return;
        }

        if (!empty($item->wholesaler_id)) {
            $this->addBucket($row, 'wholesaler', $pax, $total, $this->wholesalerNames[$item->wholesaler_id] ?? 'Wholesaler rate');
            return;
        }

        if (!empty($item->promotion_id)) {
            $promotion = $item->promotion;
            $label = trim(($promotion?->name ?? 'Promotion') . ' ' . ($promotion?->promo_code ? '(' . $promotion->promo_code . ')' : ''));
            $this->addBucket($row, 'promotion', $pax, $total, $label);
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
        $discountIncome = 0.0;
        $discountPax = 0;

        foreach ($discountItems as $discountItem) {
            $count = (int) ($discountItem->count ?? 0);
            $rate = (float) ($discountItem->final_rate ?? 0);
            $type = $discountItem->pax_type ?? 'adult';

            $coveredCounts[$type] = ($coveredCounts[$type] ?? 0) + $count;
            $discountPax += $count;
            $discountIncome += $count * $rate;

            $name = $discountItem->discount?->name
                ?? $this->discountNames[$discountItem->discount_id] ?? 'Discount';
            $row['comments'][] = $name;
        }

        $this->addBucket($row, 'discount', $discountPax, $discountIncome, null);

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

    private function addBucket(array &$row, string $bucket, int $pax, float $income, ?string $comment): void
    {
        if ($pax <= 0 && round($income, 2) == 0.0) {
            return;
        }

        $row[$bucket . '_pax'] += $pax;
        $row[$bucket . '_income'] += round($income, 2);

        if ($comment) {
            $row['comments'][] = $comment;
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
        $overrideNote = trim((string) ($item->booking_inventory?->inventory_override?->note ?? ''));
        if ($overrideNote !== '') {
            $comments[] = $overrideNote;
        }

        foreach ($this->bookingNotesByItem[$item->id] ?? [] as $note) {
            $comments[] = $note;
        }

        return implode(', ', array_values(array_unique(array_filter($comments)))) ?: 'Custom rate';
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
            'wholesaler_pax' => 0,
            'wholesaler_income' => 0.0,
            'promotion_pax' => 0,
            'promotion_income' => 0.0,
            'discount_pax' => 0,
            'discount_income' => 0.0,
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
            $row['wholesaler_pax'] ?: '',
            $this->money($row['wholesaler_income']),
            $row['promotion_pax'] ?: '',
            $this->money($row['promotion_income']),
            $row['discount_pax'] ?: '',
            $this->money($row['discount_income']),
            $row['other_pax'] ?: '',
            $this->money($row['other_income']),
            $this->money($row['total']),
            implode(', ', array_values(array_unique(array_filter($row['comments'])))),
        ];
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
}
