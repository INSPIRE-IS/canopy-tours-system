<?php

namespace App\Exports\Reports;

use App\Models\BookingItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookingAdjustmentReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, ShouldQueue
{
    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
    ) {
    }

    public function query(): Builder
    {
        $query = BookingItem::query()
            ->select('booking_items.*')
            ->with([
                'booking:id,reference,lead_name',
                'booking_inventory:id,booking_item_id,date',
                'booking_fee:id,booking_item_id,tot_amount',
                'service:id,name',
                'season:id,rake_rate_adult,rake_rate_child,rake_rate_guide',
            ])
            ->where('team_id', $this->teamId)
            ->whereHas('booking', fn (Builder $query) => $query->operational());

        if ($this->serviceId) {
            $query->where('service_id', $this->serviceId);
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

        return $query;
    }

    public function headings(): array
    {
        return [
            'Booking Reference',
            'Service',
            'Activity Date',
            'Pax Adults',
            'Pax Children',
            'Pax Guides',
            'Rack Total',
            'Pricing Adjustment',
            'Net Total',
        ];
    }

    public function map($bookingItem): array
    {
        $season = $bookingItem->season;
        $baseAdult = (float) ($season?->rake_rate_adult ?? 0);
        $baseChild = (float) ($season?->rake_rate_child ?? 0);
        $baseGuide = (float) ($season?->rake_rate_guide ?? 0);

        $paxAdults = (int) ($bookingItem->pax_adults ?? 0);
        $paxChildren = (int) ($bookingItem->pax_children ?? 0);
        $paxGuides = (int) ($bookingItem->pax_guides ?? 0);

        $rackTotal = round(($baseAdult * $paxAdults) + ($baseChild * $paxChildren) + ($baseGuide * $paxGuides), 2);
        $netTotal = round((float) ($bookingItem->booking_fee?->tot_amount ?? 0), 2);
        $adjustment = round($rackTotal - $netTotal, 2);

        return [
            $bookingItem->booking?->reference ?? '',
            $bookingItem->service?->name ?? '',
            $bookingItem->booking_inventory?->date ?? '',
            $paxAdults,
            $paxChildren,
            $paxGuides,
            number_format($rackTotal, 2, '.', ''),
            number_format($adjustment, 2, '.', ''),
            number_format($netTotal, 2, '.', ''),
        ];
    }
}
