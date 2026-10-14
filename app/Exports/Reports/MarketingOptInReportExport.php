<?php

namespace App\Exports\Reports;

use App\Models\CustomerManifest;
use App\Models\QuestionAnswer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MarketingOptInReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, ShouldQueue
{
    private const ANSWER_SLUGS = [
        'full_name',
        'email_address',
        'marketing_opt_in',
        'birthday_month',
        'birthday_day',
    ];

    private array $answersByManifestKey = [];

    public function __construct(
        private readonly int $teamId,
        private readonly ?string $startDate,
        private readonly ?string $endDate,
        private readonly ?int $serviceId,
    ) {
    }

    public function collection(): Collection
    {
        $manifests = CustomerManifest::query()
            ->select('customer_manifests.*')
            ->with([
                'customer:id,full_name,email_address',
                'booking_item:id,booking_id,service_id',
                'booking_item.booking:id,deleted_at',
                'booking_item.booking_inventory:id,booking_item_id,date',
            ])
            ->where('team_id', $this->teamId)
            ->where('is_indemnity', true)
            ->whereHas('booking_item', function (Builder $query) {
                $query->whereNull('deleted_at');

                if ($this->serviceId) {
                    $query->where('service_id', $this->serviceId);
                }
            })
            ->whereHas('booking_item.booking', function (Builder $query) {
                $query->whereNull('deleted_at')->operational();
            })
            ->when($this->startDate || $this->endDate, function (Builder $query) {
                $query->whereHas('booking_item.booking_inventory', function (Builder $sub) {
                    if ($this->startDate) {
                        $sub->whereDate('date', '>=', $this->startDate);
                    }
                    if ($this->endDate) {
                        $sub->whereDate('date', '<=', $this->endDate);
                    }
                });
            })
            ->get();

        $this->loadAnswers($manifests);

        return $manifests
            ->filter(fn(CustomerManifest $manifest) => $this->isEligible($manifest))
            ->sortByDesc(fn(CustomerManifest $manifest) => $manifest->booking_item?->booking_inventory?->date ?? '')
            ->unique(fn(CustomerManifest $manifest) => strtolower(trim((string) $manifest->customer?->email_address)))
            ->values();
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Email',
            'Birthday Month',
            'Birthday Day',
            'Marketing Opt-In',
        ];
    }

    public function map($manifest): array
    {
        $answers = $this->answersFor($manifest);
        $email = $this->emailFor($manifest, $answers);
        $fullName = (string) ($manifest->customer?->full_name ?: ($answers['full_name'] ?? ''));
        [$firstName, $lastName] = $this->splitName($fullName);

        return [
            $firstName,
            $lastName,
            $email,
            $this->formatBirthdayMonth($answers['birthday_month'] ?? ''),
            $answers['birthday_day'] ?? '',
            'Yes',
        ];
    }

    private function loadAnswers(Collection $manifests): void
    {
        $bookingItemIds = $manifests->pluck('booking_item_id')->filter()->unique()->values();
        $customerIds = $manifests->pluck('customer_id')->filter()->unique()->values();

        if ($bookingItemIds->isEmpty() || $customerIds->isEmpty()) {
            $this->answersByManifestKey = [];
            return;
        }

        $this->answersByManifestKey = QuestionAnswer::with('question:id,slug')
            ->whereIn('booking_item_id', $bookingItemIds)
            ->whereIn('customer_id', $customerIds)
            ->whereHas('question', fn(Builder $query) => $query->whereIn('slug', self::ANSWER_SLUGS))
            ->get()
            ->groupBy(fn(QuestionAnswer $answer) => $this->answerKey($answer->booking_item_id, $answer->customer_id))
            ->map(function (Collection $answers) {
                $mapped = [];
                foreach ($answers as $answer) {
                    $slug = $answer->question?->slug;
                    if ($slug) {
                        $mapped[$slug] = $answer->answer;
                    }
                }
                return $mapped;
            })
            ->toArray();
    }

    private function isEligible(CustomerManifest $manifest): bool
    {
        $answers = $this->answersFor($manifest);
        $email = $this->emailFor($manifest, $answers);

        return $email !== ''
            && $this->isYes($answers['marketing_opt_in'] ?? null)
            && trim((string) ($answers['birthday_month'] ?? '')) !== ''
            && trim((string) ($answers['birthday_day'] ?? '')) !== '';
    }

    private function emailFor(CustomerManifest $manifest, array $answers): string
    {
        return trim((string) ($manifest->customer?->email_address ?: ($answers['email_address'] ?? '')));
    }

    private function answersFor(CustomerManifest $manifest): array
    {
        return $this->answersByManifestKey[$this->answerKey($manifest->booking_item_id, $manifest->customer_id)] ?? [];
    }

    private function answerKey(?int $bookingItemId, ?int $customerId): string
    {
        return (int) $bookingItemId . ':' . (int) $customerId;
    }

    private function isYes($value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'true', 'y'], true);
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function formatBirthdayMonth($value): string
    {
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $numeric = (int) $value;

        return $months[$numeric] ?? (string) $value;
    }
}
