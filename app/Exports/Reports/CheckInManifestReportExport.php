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
use Illuminate\Support\Facades\Log;
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
        private readonly bool $includeMedical,
        private readonly bool $includeMedicalDetail,
        private readonly bool $includeFitness,
        private readonly bool $includePregnancy,
        private readonly bool $includeDuplicateCheckins,
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
        Log::info('Check-in manifest report export started', [
            'team_id' => $this->teamId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'service_id' => $this->serviceId,
            'include_duplicate_checkins' => $this->includeDuplicateCheckins,
            'filter_marker' => 'active_booking_items_only_v2',
        ]);

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

        // Operational manifest exports need active booking/date context; orphan/cancelled legacy rows produce blank report lines.
        $query->whereIn('customer_manifests.booking_item_id', function ($activeItems) {
            $activeItems->select('manifest_booking_items.id')
                ->from('booking_items as manifest_booking_items')
                ->join('bookings as manifest_bookings', 'manifest_bookings.id', '=', 'manifest_booking_items.booking_id')
                ->join('booking_inventories as manifest_booking_inventories', 'manifest_booking_inventories.booking_item_id', '=', 'manifest_booking_items.id')
                ->whereNull('manifest_booking_items.deleted_at')
                ->whereNull('manifest_bookings.deleted_at')
                ->whereNull('manifest_booking_inventories.deleted_at')
                ->whereNotNull('manifest_booking_inventories.date');
        });

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

        if (!$this->includeDuplicateCheckins) {
            $query->whereRaw(
                "(customer_manifests.customer_id IS NULL OR customer_manifests.id = (
                    SELECT latest_manifests.id
                    FROM customer_manifests AS latest_manifests
                    LEFT JOIN customers AS latest_customers ON latest_customers.id = latest_manifests.customer_id
                    LEFT JOIN customers AS current_customers ON current_customers.id = customer_manifests.customer_id
                    WHERE latest_manifests.booking_item_id = customer_manifests.booking_item_id
                        AND latest_manifests.deleted_at IS NULL
                        AND (
                            latest_manifests.customer_id = customer_manifests.customer_id
                            OR (
                                current_customers.full_name IS NOT NULL
                                AND TRIM(current_customers.full_name) != ''
                                AND LOWER(TRIM(latest_customers.full_name)) = LOWER(TRIM(current_customers.full_name))
                            )
                        )
                    ORDER BY latest_manifests.updated_at DESC, latest_manifests.id DESC
                    LIMIT 1
                ))"
            );
        }

        Log::debug('Check-in manifest report query prepared', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filter_marker' => 'active_booking_items_only_v2',
        ]);

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
        if ($this->includeMedical) {
            $headings[] = 'Medical Condition';
        }
        if ($this->includeMedicalDetail) {
            $headings[] = 'Medical Details';
        }
        if ($this->includeFitness) {
            $headings[] = 'Fit To Walk';
        }
        if ($this->includePregnancy) {
            $headings[] = 'Pregnancy';
        }
        if ($this->includeDuplicateCheckins) {
            $headings[] = 'Manifest Created At';
            $headings[] = 'Manifest Updated At';
        }

        return $headings;
    }

    public function map($manifest): array
    {
        $bookingItem = $manifest->booking_item;
        $booking = $bookingItem?->booking;
        $inventory = $bookingItem?->booking_inventory;
        $timeSlot = $inventory?->inventory_time_slot;

        if (!$booking || !$bookingItem || !$inventory || !$inventory?->date) {
            Log::warning('Check-in manifest report row missing booking context after filters', [
                'manifest_id' => $manifest->id,
                'manifest_booking_item_id' => $manifest->booking_item_id,
                'customer_id' => $manifest->customer_id,
                'customer_name' => $manifest->customer?->full_name,
                'has_booking_item' => (bool) $bookingItem,
                'has_booking' => (bool) $booking,
                'has_inventory' => (bool) $inventory,
                'inventory_date' => $inventory?->date,
                'filter_marker' => 'active_booking_items_only_v2',
            ]);
        }

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

        if (
            $this->includeGroupType ||
            $this->includeNationality ||
            $this->includeReferral ||
            $this->includeGender ||
            $this->includeMedical ||
            $this->includeMedicalDetail ||
            $this->includeFitness ||
            $this->includePregnancy
        ) {
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
            if ($this->includeMedical) {
                $row[] = $this->yesNoAnswer($answers['medical_id'] ?? null);
            }
            if ($this->includeMedicalDetail) {
                $row[] = $answers['medical_detail'] ?? '';
            }
            if ($this->includeFitness) {
                $row[] = $this->yesNoAnswer($answers['is_fit'] ?? null);
            }
            if ($this->includePregnancy) {
                $row[] = $this->yesNoAnswer($answers['pregnant'] ?? null);
            }
        }

        if ($this->includeDuplicateCheckins) {
            $row[] = $manifest->created_at?->format('Y-m-d H:i:s') ?? '';
            $row[] = $manifest->updated_at?->format('Y-m-d H:i:s') ?? '';
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

    private function yesNoAnswer($value): string
    {
        return match ((string) $value) {
            '1', 'true', 'TRUE', 'on' => 'Yes',
            '2', '0', 'false', 'FALSE', 'off' => 'No',
            default => $value === null || $value === '' ? '' : (string) $value,
        };
    }
}
