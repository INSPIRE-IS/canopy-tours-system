<?php

namespace App\Exports\Reports;

use App\Models\BookingItem;
use App\Models\BookingNote;
use App\Models\Promotion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PromoCodeUsageReportExport implements FromCollection, ShouldAutoSize, ShouldQueue
{
    private array $promotionsById = [];

    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
        private readonly ?int $promotionId,
    ) {
    }

    public function collection(): Collection
    {
        $items = $this->query()->get();
        $this->loadPromotions($items);

        $rows = collect();

        $rows->push([
            'Promo Name',
            'Promo Code',
            'Promo Type',
            'Bookings',
            'Activity Items',
            'Total Pax',
            'Rack Total',
            'Net Total',
            'Promo Adjustment',
            'Amount Paid',
        ]);

        foreach ($items->groupBy(fn (BookingItem $item) => (string) ($this->promoId($item) ?: 'unknown')) as $group) {
            $first = $group->first();
            $rackTotal = $group->sum(fn (BookingItem $item) => $this->rackTotal($item));
            $netTotal = $group->sum(fn (BookingItem $item) => $this->netTotal($item));

            $rows->push([
                $this->promoName($first),
                $this->promoCode($first),
                $this->promoType($first),
                $group->pluck('booking_id')->unique()->count(),
                $group->count(),
                $group->sum(fn (BookingItem $item) => $this->paxTotal($item)),
                $this->money($rackTotal),
                $this->money($netTotal),
                $this->money($rackTotal - $netTotal),
                $this->money($group->sum(fn (BookingItem $item) => (float) ($item->booking_fee?->tot_paid ?? 0))),
            ]);
        }

        $rows->push($this->blankRow());
        $rows->push([
            'Booking Reference',
            'Booking Status',
            'Booking Source',
            'Lead Name',
            'Booking Date',
            'Activity Date',
            'Activity Time',
            'Service',
            'Promo Name',
            'Promo Code',
            'Promo Type',
            'Apply To / Mode',
            'Pax Adults',
            'Pax Children',
            'Pax Guides',
            'Total Pax',
            'Rack Total',
            'Promo Adjustment',
            'Net Total',
            'Amount Paid',
            'Fully Paid',
            'Agent/User',
            'Booking Note',
        ]);

        foreach ($items as $item) {
            $rackTotal = $this->rackTotal($item);
            $netTotal = $this->netTotal($item);
            $paid = (float) ($item->booking_fee?->tot_paid ?? 0);

            $rows->push([
                $item->booking?->reference ?? '',
                'Active',
                $item->booking_detail?->is_web_booking ? 'Web' : 'Manual',
                $item->booking?->lead_name ?? '',
                optional($item->booking?->created_at)?->format('Y-m-d') ?? '',
                $this->activityDate($item) ?? '',
                $this->activityTime($item) ?? '',
                $item->service?->name ?? '',
                $this->promoName($item),
                $this->promoCode($item),
                $this->promoType($item),
                $this->promoMode($item),
                (int) ($item->pax_adults ?? 0),
                (int) ($item->pax_children ?? 0),
                (int) ($item->pax_guides ?? 0),
                $this->paxTotal($item),
                $this->money($rackTotal),
                $this->money($rackTotal - $netTotal),
                $this->money($netTotal),
                $this->money($paid),
                $netTotal > 0 && $paid >= $netTotal ? 'Yes' : 'No',
                $item->booking?->user?->name ?? '',
                $item->latest_booking_note ?? '',
            ]);
        }

        return $rows;
    }

    private function query(): Builder
    {
        $query = BookingItem::query()
            ->select('booking_items.*')
            ->addSelect([
                'latest_booking_note' => BookingNote::select('note')
                    ->whereColumn('booking_id', 'booking_items.booking_id')
                    ->whereColumn('booking_item_id', 'booking_items.id')
                    ->where('type', 'booking_note')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->with([
                'booking:id,reference,lead_name,user_id,created_at,deleted_at',
                'booking.user:id,name',
                'booking_detail:id,booking_item_id,is_web_booking,deleted_at',
                'booking_fee:id,booking_item_id,tot_amount,tot_paid,deleted_at',
                'booking_inventory:id,booking_item_id,date,inventory_time_slot_id,deleted_at',
                'booking_inventory.inventory_time_slot:id,start_time,deleted_at',
                'price_lines:id,booking_item_id,source_type,source_id,pax_type,count,base_rate,final_rate,line_total,activity_time,meta,deleted_at',
                'promotion:id,name,promo_code,type,value,params',
                'service:id,name',
                'season:id,name,rake_rate_adult,rake_rate_child,rake_rate_guide',
            ])
            ->where('team_id', $this->teamId)
            ->whereHas('booking', fn (Builder $query) => $query->whereNull('deleted_at'))
            ->where(function (Builder $query) {
                $query->whereNotNull('promotion_id')
                    ->orWhereHas('price_lines', fn (Builder $sub) => $sub->where('source_type', 'promotion'));
            });

        if ($this->serviceId) {
            $query->where('service_id', $this->serviceId);
        }

        if ($this->promotionId) {
            $query->where(function (Builder $query) {
                $query->where('promotion_id', $this->promotionId)
                    ->orWhereHas('price_lines', function (Builder $sub) {
                        $sub->where('source_type', 'promotion')
                            ->where('source_id', $this->promotionId);
                    });
            });
        }

        if ($this->startDate || $this->endDate) {
            $query->whereHas('booking_inventory', function (Builder $sub) {
                if ($this->startDate) {
                    $sub->whereDate('date', '>=', $this->startDate);
                }
                if ($this->endDate) {
                    $sub->whereDate('date', '<=', $this->endDate);
                }
            });
        }

        return $query
            ->orderByRaw('(select date from booking_inventories where booking_inventories.booking_item_id = booking_items.id and booking_inventories.deleted_at is null limit 1) asc')
            ->orderBy('id');
    }

    private function loadPromotions(Collection $items): void
    {
        $ids = $items
            ->flatMap(fn (BookingItem $item) => [
                $item->promotion_id,
                ...($item->price_lines?->where('source_type', 'promotion')->pluck('source_id')->all() ?? []),
            ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->promotionsById = empty($ids)
            ? []
            : Promotion::select('id', 'name', 'promo_code', 'type', 'value', 'params')
                ->where('team_id', $this->teamId)
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id')
                ->all();
    }

    private function promoId(?BookingItem $item): ?int
    {
        if (!$item) {
            return null;
        }

        if ($item->promotion_id) {
            return (int) $item->promotion_id;
        }

        $line = $item->price_lines?->firstWhere('source_type', 'promotion');

        return $line?->source_id ? (int) $line->source_id : null;
    }

    private function promotion(?BookingItem $item): ?Promotion
    {
        if (!$item) {
            return null;
        }

        if ($item->promotion) {
            return $item->promotion;
        }

        $promoId = $this->promoId($item);

        return $promoId ? ($this->promotionsById[$promoId] ?? null) : null;
    }

    private function promoName(?BookingItem $item): string
    {
        $promotion = $this->promotion($item);

        return $promotion?->name
            ?? $item?->price_lines?->firstWhere('source_type', 'promotion')?->label
            ?? 'Promotion';
    }

    private function promoCode(?BookingItem $item): string
    {
        $promotion = $this->promotion($item);

        return $promotion?->promo_code
            ?? (string) data_get($item?->price_lines?->firstWhere('source_type', 'promotion')?->meta, 'promo_code', '');
    }

    private function promoType(?BookingItem $item): string
    {
        return $this->promotion($item)?->type ?? '';
    }

    private function promoMode(?BookingItem $item): string
    {
        $promotion = $this->promotion($item);
        if (!$promotion) {
            return '';
        }

        $params = $promotion->params;
        if (is_string($params)) {
            $params = json_decode($params, true) ?: [];
        }

        return match ($promotion->type) {
            'fixed_value' => (string) data_get($params, 'fixed_value_mode', 'booking'),
            'percent' => 'percentage',
            'graduated' => 'graduated',
            'varied_child' => 'child rule',
            'volume_discount' => 'volume',
            'pax_exact_discount' => 'exact pax',
            'time_slot' => 'time slot',
            default => '',
        };
    }

    private function rackTotal(BookingItem $item): float
    {
        $priceLines = $item->price_lines ?? collect();
        if ($priceLines->isNotEmpty()) {
            return round((float) $priceLines->sum(fn ($line) => (float) $line->base_rate * (int) $line->count), 2);
        }

        return round(
            ((float) ($item->season?->rake_rate_adult ?? 0) * (int) ($item->pax_adults ?? 0))
            + ((float) ($item->season?->rake_rate_child ?? 0) * (int) ($item->pax_children ?? 0))
            + ((float) ($item->season?->rake_rate_guide ?? 0) * (int) ($item->pax_guides ?? 0)),
            2
        );
    }

    private function netTotal(BookingItem $item): float
    {
        if ($item->booking_fee) {
            return round((float) ($item->booking_fee->tot_amount ?? 0), 2);
        }

        $priceLines = $item->price_lines ?? collect();
        if ($priceLines->isNotEmpty()) {
            return round((float) $priceLines->sum(fn ($line) => (float) $line->line_total), 2);
        }

        return round(
            ((float) ($item->price_adult ?? 0) * (int) ($item->pax_adults ?? 0))
            + ((float) ($item->price_child ?? 0) * (int) ($item->pax_children ?? 0))
            + ((float) ($item->price_guide ?? 0) * (int) ($item->pax_guides ?? 0)),
            2
        );
    }

    private function paxTotal(BookingItem $item): int
    {
        return (int) ($item->pax_adults ?? 0)
            + (int) ($item->pax_children ?? 0)
            + (int) ($item->pax_guides ?? 0);
    }

    private function activityDate(BookingItem $item): ?string
    {
        $raw = $item->booking_inventory?->getRawOriginal('date') ?? $item->booking_inventory?->date;

        return $raw ? date('Y-m-d', strtotime($raw)) : null;
    }

    private function activityTime(BookingItem $item): ?string
    {
        return $item->booking_inventory?->inventory_time_slot?->start_time
            ?? $item->price_lines?->first()?->activity_time;
    }

    private function money(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    private function blankRow(): array
    {
        return array_fill(0, 23, '');
    }
}
