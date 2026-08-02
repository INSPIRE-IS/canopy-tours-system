<?php

namespace App\Exports\Reports;

use App\Models\BookingItem;
use App\Models\BookingNote;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookingsReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, ShouldQueue
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
            ->addSelect([
                'latest_booking_note' => BookingNote::select('note')
                    ->whereColumn('booking_id', 'booking_items.booking_id')
                    ->whereColumn('booking_item_id', 'booking_items.id')
                    ->where('type', 'booking_note')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->with([
                'booking' => function ($query) {
                    $query->select('id', 'reference', 'lead_name', 'user_id', 'created_at', 'deleted_at');
                },
                'booking.user:id,name',
                'booking_detail' => function ($query) {
                    $query->select('id', 'booking_item_id', 'is_web_booking', 'deleted_at');
                },
                'booking_fee' => function ($query) {
                    $query->select('id', 'booking_item_id', 'tot_amount', 'tot_paid', 'deleted_at');
                },
                'booking_inventory:id,booking_item_id,date',
                'service:id,name',
            ])
            ->where('team_id', $this->teamId)
            ->whereHas('booking');

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
            'Booking Status',
            'Booking Source',
            'Lead Name',
            'Booking Date',
            'Activity Date',
            'Agent/User',
            'Service',
            'Pax Adults',
            'Pax Children',
            'Pax Guides',
            'Total Amount',
            'Amount Paid',
            'Fully Paid',
            'Booking Note',
        ];
    }

    public function map($bookingItem): array
    {
        $fee = $bookingItem->booking_fee;
        $totalAmount = (float) ($fee?->tot_amount ?? 0);
        $amountPaid = (float) ($fee?->tot_paid ?? 0);
        $fullyPaid = $totalAmount > 0 && $amountPaid >= $totalAmount ? 'Yes' : 'No';
        $source = $bookingItem->booking_detail?->is_web_booking ? 'Web' : 'Manual';
        $bookingStatus = ($bookingItem->booking?->deleted_at || $bookingItem->deleted_at) ? 'Cancelled' : 'Active';

        return [
            $bookingItem->booking?->reference ?? '',
            $bookingStatus,
            $source,
            $bookingItem->booking?->lead_name ?? '',
            optional($bookingItem->booking?->created_at)?->format('Y-m-d') ?? '',
            $bookingItem->booking_inventory?->date ?? '',
            $bookingItem->booking?->user?->name ?? '',
            $bookingItem->service?->name ?? '',
            (int) ($bookingItem->pax_adults ?? 0),
            (int) ($bookingItem->pax_children ?? 0),
            (int) ($bookingItem->pax_guides ?? 0),
            number_format($totalAmount, 2, '.', ''),
            number_format($amountPaid, 2, '.', ''),
            $fullyPaid,
            $bookingItem->latest_booking_note ?? '',
        ];
    }
}
