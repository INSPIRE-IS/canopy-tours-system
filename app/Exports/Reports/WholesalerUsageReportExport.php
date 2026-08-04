<?php

namespace App\Exports\Reports;

use App\Models\BookingItem;
use App\Models\BookingNote;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class WholesalerUsageReportExport implements FromCollection, ShouldAutoSize, ShouldQueue
{
    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
        private readonly ?int $wholesalerId,
    ) {
    }

    public function collection(): Collection
    {
        $items = $this->query()->get();
        $rows = collect();

        $rows->push([
            'Wholesaler',
            'Trade Exchange',
            'Prepaid Voucher Partner',
            'Bookings',
            'Activity Items',
            'Total Pax',
            'Rack Total',
            'Wholesaler Total',
            'Adjustment',
            'Amount Paid',
            'Balance Due',
        ]);

        foreach ($items->groupBy(fn (BookingItem $item) => (string) ($item->wholesaler_id ?: 'unknown')) as $group) {
            $first = $group->first();
            $rackTotal = $group->sum(fn (BookingItem $item) => $this->rackTotal($item));
            $wholesalerTotal = $group->sum(fn (BookingItem $item) => $this->netTotal($item));
            $paidTotal = $group->sum(fn (BookingItem $item) => (float) ($item->booking_fee?->tot_paid ?? 0));

            $rows->push([
                $first?->wholesaler?->name ?? 'Wholesaler',
                $this->yesNo((bool) ($first?->wholesaler?->is_trade_exchange ?? false)),
                $this->yesNo($group->contains(fn (BookingItem $item) => $this->isPrepaidVoucher($item))),
                $group->pluck('booking_id')->unique()->count(),
                $group->count(),
                $group->sum(fn (BookingItem $item) => $this->paxTotal($item)),
                $this->money($rackTotal),
                $this->money($wholesalerTotal),
                $this->money($rackTotal - $wholesalerTotal),
                $this->money($paidTotal),
                $this->money(max(0, $wholesalerTotal - $paidTotal)),
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
            'Season',
            'Wholesaler',
            'Wholesaler Rate ID',
            'Trade Exchange',
            'Prepaid Voucher Partner',
            'Bill Back',
            'Pax Adults',
            'Pax Children',
            'Pax Guides',
            'Total Pax',
            'Rack Total',
            'Wholesaler Total',
            'Adjustment',
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
                $item->season?->name ?? '',
                $item->wholesaler?->name ?? '',
                $item->wholesaler_rate_id ?? '',
                $this->yesNo((bool) ($item->wholesaler?->is_trade_exchange ?? false)),
                $this->yesNo($this->isPrepaidVoucher($item)),
                $this->yesNo((bool) ($item->wholesaler_rate?->bill_back ?? false)),
                (int) ($item->pax_adults ?? 0),
                (int) ($item->pax_children ?? 0),
                (int) ($item->pax_guides ?? 0),
                $this->paxTotal($item),
                $this->money($rackTotal),
                $this->money($netTotal),
                $this->money($rackTotal - $netTotal),
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
                'booking_fee:id,booking_item_id,tot_amount,tot_paid,is_prepaid_wholesaler_voucher,deleted_at',
                'booking_inventory:id,booking_item_id,date,inventory_time_slot_id,deleted_at',
                'booking_inventory.inventory_time_slot:id,start_time,deleted_at',
                'price_lines:id,booking_item_id,source_type,source_id,pax_type,count,base_rate,final_rate,line_total,activity_time,meta,deleted_at',
                'service:id,name',
                'season:id,name,rake_rate_adult,rake_rate_child,rake_rate_guide',
                'wholesaler:id,name,is_trade_exchange',
                'wholesaler_rate:id,wholesaler_id,service_id,season_id,price_adult,price_child,price_guide,bill_back,is_prepaid_voucher',
            ])
            ->where('team_id', $this->teamId)
            ->whereHas('booking', fn (Builder $query) => $query->whereNull('deleted_at'))
            ->where(function (Builder $query) {
                $query->whereNotNull('wholesaler_id')
                    ->orWhereHas('price_lines', fn (Builder $sub) => $sub->where('source_type', 'wholesaler'));
            });

        if ($this->serviceId) {
            $query->where('service_id', $this->serviceId);
        }

        if ($this->wholesalerId) {
            $query->where(function (Builder $query) {
                $query->where('wholesaler_id', $this->wholesalerId)
                    ->orWhereHas('price_lines', function (Builder $sub) {
                        $sub->where('source_type', 'wholesaler')
                            ->where('source_id', $this->wholesalerId);
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

    private function isPrepaidVoucher(BookingItem $item): bool
    {
        return (bool) ($item->wholesaler_rate?->is_prepaid_voucher ?? false)
            || (bool) ($item->booking_fee?->is_prepaid_wholesaler_voucher ?? false)
            || (bool) ($item->price_lines?->contains(fn ($line) => (bool) data_get($line->meta, 'is_prepaid_wholesaler_voucher')) ?? false);
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    private function money(float $amount): string
    {
        return number_format(round($amount, 2), 2, '.', '');
    }

    private function blankRow(): array
    {
        return array_fill(0, 25, '');
    }
}
