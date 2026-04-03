<?php

namespace App\Exports\Reports;

use App\Models\BookingPayment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;

class PaymentsReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, ShouldQueue
{
    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
        private readonly ?int $typePaymentId,
    ) {
    }

    public function query(): Builder
    {
        $query = BookingPayment::query()
            ->select('booking_payments.*')
            ->with([
                'booking' => function ($query) {
                    $query->withTrashed()
                        ->select('id', 'reference', 'lead_name', 'created_at', 'grand_tot_due', 'tot_paid', 'deleted_at');
                },
                'booking.booking_items' => function ($query) {
                    $query->withTrashed()->select('id', 'booking_id', 'service_id');
                },
                'booking.booking_items.service:id,name',
                'booking.booking_items.booking_inventory:id,booking_item_id,date',
                'booking.booking_items.booking_detail' => function ($query) {
                    $query->withTrashed()->select('id', 'booking_item_id', 'is_web_booking', 'deleted_at');
                },
                'type_payment:id,name',
            ])
            ->where('team_id', $this->teamId);

        if ($this->typePaymentId) {
            $query->where('type_payment_id', $this->typePaymentId);
        }

        if ($this->serviceId) {
            $query->whereHas('booking.booking_items', function (Builder $sub) {
                $sub->where('service_id', $this->serviceId);
            });
        }

        if ($this->startDate) {
            $query->whereDate('payment_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('payment_date', '<=', $this->endDate);
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
            'Service',
            'Payment Method',
            'Payment Date',
            'Ex VAT Amount (stored)',
            'VAT Amount (stored)',
            'Payment Amount',
        ];
    }

    public function map($payment): array
    {
        $booking = $payment->booking;
        $bookingItems = $booking?->booking_items ?? collect();
        $bookingItem = null;

        if ($this->serviceId) {
            $bookingItem = $bookingItems->firstWhere('service_id', $this->serviceId);
        }
        if (!$bookingItem) {
            $bookingItem = $bookingItems->first();
        }

        $source = $bookingItem?->booking_detail?->is_web_booking ? 'Web' : 'Manual';
        $exVat = (float) ($payment->ex_vat_amount ?? 0);
        $vatAmount = $payment->vat_amount !== null ? (float) $payment->vat_amount : null;
        if ($vatAmount === null) {
            $totalAmount = $exVat;
        } elseif ($vatAmount >= $exVat && $exVat > 0) {
            // Stored vat_amount is clearly not a VAT portion; fall back to ex_vat_amount.
            $totalAmount = $exVat;
        } else {
            // Front/simulated flows store vat_amount as VAT portion.
            $totalAmount = $exVat + $vatAmount;
        }

        $bookingStatus = $booking?->deleted_at ? 'Cancelled' : 'Active';

        return [
            $booking?->reference ?? '',
            $bookingStatus,
            $source,
            $booking?->lead_name ?? '',
            optional($booking?->created_at)?->format('Y-m-d') ?? '',
            $bookingItem?->booking_inventory?->date ?? '',
            $bookingItem?->service?->name ?? '',
            $payment->type_payment?->name ?? '',
            $payment->getRawOriginal('payment_date') ?? '',
            number_format($exVat, 2, '.', ''),
            $vatAmount !== null ? number_format($vatAmount, 2, '.', '') : '',
            number_format($totalAmount, 2, '.', ''),
        ];
    }
}
