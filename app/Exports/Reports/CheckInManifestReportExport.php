<?php

namespace App\Exports\Reports;

use App\Models\BookingNote;
use App\Models\Country;
use App\Models\CustomerManifest;
use App\Models\QuestionAnswer;
use App\Models\TypeGender;
use App\Models\TypeGroup;
use App\Models\TypeReferral;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CheckInManifestReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, ShouldQueue
{
    private array $answersByBookingItem = [];
    private array $typeGroupMap = [];
    private array $typeGenderMap = [];
    private array $typeReferralMap = [];
    private array $countryMap = [];

    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
        private readonly bool $includeGroupType,
        private readonly bool $includeNationality,
        private readonly bool $includeReferral,
        private readonly bool $includeGender,
    ) {
        $this->typeGroupMap = TypeGroup::select('id', 'name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        $this->typeGenderMap = TypeGender::select('id', 'name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        $this->typeReferralMap = TypeReferral::select('id', 'name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();

        $this->countryMap = Country::select('id', 'nationality', 'name')
            ->get()
            ->mapWithKeys(function ($country) {
                return [$country->id => $country->nationality ?: $country->name];
            })
            ->toArray();
    }

    public function query(): Builder
    {
        $query = CustomerManifest::query()
            ->select('customer_manifests.*')
            ->addSelect([
                'latest_booking_note' => BookingNote::select('note')
                    ->whereColumn('booking_item_id', 'customer_manifests.booking_item_id')
                    ->where('type', 'booking_note')
                    ->orderByDesc('created_at')
                    ->limit(1),
            ])
            ->with([
                'customer:id,full_name,nationality_id',
                'booking_item:id,booking_id,service_id',
                'booking_item.booking:id,reference,lead_name',
                'booking_item.service:id,name',
                'booking_item.booking_inventory:id,booking_item_id,date,inventory_time_slot_id',
                'booking_item.booking_inventory.inventory_time_slot:id,start_time',
            ])
            ->where('team_id', $this->teamId);

        if ($this->serviceId) {
            $query->whereHas('booking_item', function (Builder $sub) {
                $sub->where('service_id', $this->serviceId);
            });
        }

        if ($this->startDate || $this->endDate) {
            $query->whereHas('booking_item.booking_inventory', function (Builder $sub) {
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
        $headings = [
            'Booking Reference',
            'Service',
            'Activity Date',
            'Activity Time',
            'Lead Name',
            'Checked-in Customer',
            'Checked In',
            'Arrived',
            'Booking Note',
        ];

        if ($this->includeGroupType) {
            $headings[] = 'Group Type';
        }
        if ($this->includeNationality) {
            $headings[] = 'Nationality';
        }
        if ($this->includeReferral) {
            $headings[] = 'Referral Captured';
        }
        if ($this->includeGender) {
            $headings[] = 'Gender';
        }

        return $headings;
    }

    public function map($manifest): array
    {
        $bookingItem = $manifest->booking_item;
        $booking = $bookingItem?->booking;
        $inventory = $bookingItem?->booking_inventory;
        $timeSlot = $inventory?->inventory_time_slot;

        $checkedIn = $manifest->is_indemnity ? 'Yes' : 'No';
        $arrived = $manifest->attendance_status === 'arrived' ? 'Yes' : 'No';

        $row = [
            $booking?->reference ?? '',
            $bookingItem?->service?->name ?? '',
            $inventory?->date ?? '',
            $timeSlot?->start_time ? substr($timeSlot->start_time, 0, 5) : '',
            $booking?->lead_name ?? '',
            $manifest->customer?->full_name ?? '',
            $checkedIn,
            $arrived,
            $manifest->latest_booking_note ?? '',
        ];

        if ($this->includeGroupType || $this->includeNationality || $this->includeReferral || $this->includeGender) {
            $bookingItemId = $bookingItem?->id;
            $customerId = $manifest->customer_id;
            $answers = $this->getAnswersForCustomer($bookingItemId, $customerId);

            if ($this->includeGroupType) {
                $groupValue = $answers['type_group_id'] ?? $answers['group_type'] ?? null;
                $row[] = $groupValue !== null
                    ? ($this->typeGroupMap[(string) $groupValue] ?? $groupValue)
                    : '';
            }
            if ($this->includeNationality) {
                $countryValue = $answers['country_id'] ?? $answers['nationality_id'] ?? $manifest->customer?->nationality_id;
                $row[] = $countryValue !== null
                    ? ($this->countryMap[(string) $countryValue] ?? $countryValue)
                    : '';
            }
            if ($this->includeReferral) {
                $referralValue = $answers['referral_id'] ?? null;
                $row[] = $referralValue !== null
                    ? ($this->typeReferralMap[(string) $referralValue] ?? $referralValue)
                    : '';
            }
            if ($this->includeGender) {
                $genderValue = $answers['gender'] ?? $answers['gender_id'] ?? null;
                $row[] = $genderValue !== null
                    ? ($this->typeGenderMap[(string) $genderValue] ?? $genderValue)
                    : '';
            }
        }

        return $row;
    }

    private function getAnswersForCustomer(?int $bookingItemId, ?int $customerId): array
    {
        if (!$bookingItemId || !$customerId) {
            return [];
        }

        if (!array_key_exists($bookingItemId, $this->answersByBookingItem)) {
            $answers = QuestionAnswer::with('question:id,slug')
                ->where('booking_item_id', $bookingItemId)
                ->get()
                ->groupBy('customer_id')
                ->map(function ($rows) {
                    $mapped = [];
                    foreach ($rows as $answer) {
                        $slug = $answer->question?->slug;
                        if ($slug) {
                            $mapped[$slug] = $answer->answer;
                        }
                    }
                    return $mapped;
                })
                ->toArray();

            $this->answersByBookingItem[$bookingItemId] = $answers;
        }

        return $this->answersByBookingItem[$bookingItemId][$customerId] ?? [];
    }
}
