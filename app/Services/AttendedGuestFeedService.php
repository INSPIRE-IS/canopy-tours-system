<?php

namespace App\Services;

use App\Models\Country;
use App\Models\QuestionAnswer;
use App\Models\State;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendedGuestFeedService
{
    public const TOKEN_ABILITY = 'everlytic:read';

    private const CONTACT_ANSWER_SLUGS = [
        'full_name',
        'email_address',
        'country_id',
        'province_id',
        'birthday_month',
        'birthday_day',
        'marketing_opt_in',
    ];

    private const EXPLICIT_CONSENT_ANSWERS = [
        '0', '1', '2', 'yes', 'Yes', 'YES', 'no', 'No', 'NO',
        'true', 'True', 'TRUE', 'false', 'False', 'FALSE', 'y', 'Y', 'n', 'N',
    ];

    private const YES_CONSENT_ANSWERS = [
        '1', 'yes', 'Yes', 'YES', 'true', 'True', 'TRUE', 'y', 'Y',
    ];

    public function snapshot(
        Team $team,
        CarbonImmutable $activityFrom,
        CarbonImmutable $activityTo,
        int $pageSize,
    ): array {
        $query = $this->latestConsentQuery($team->id)
            ->whereIn('question_answers.answer', self::YES_CONSENT_ANSWERS);

        $this->whereAttended($query, $team->id, $activityFrom, $activityTo);

        $paginator = $query
            ->orderBy('question_answers.id')
            ->cursorPaginate($pageSize)
            ->withQueryString();
        $consents = collect($paginator->items());
        $answersByCustomer = $this->answersByCustomer($consents, $team->id);
        $activityByCustomer = $this->latestAttendanceByCustomer(
            $consents,
            $team->id,
            $activityFrom,
            $activityTo,
        );
        [$countries, $provinces] = $this->locations($answersByCustomer, $consents);

        $data = $consents->map(fn (QuestionAnswer $consent) => $this->mapContact(
            $consent,
            $team,
            $answersByCustomer[$consent->customer_id] ?? collect(),
            $activityByCustomer[$consent->customer_id] ?? null,
            $countries,
            $provinces,
        ))->values()->all();

        return [
            'data' => $data,
            'links' => [
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'activity_from' => $activityFrom->toDateString(),
                'activity_to' => $activityTo->toDateString(),
                'returned' => count($data),
                'limit' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
                'next_cursor' => $paginator->nextCursor()?->encode(),
            ],
        ];
    }

    private function latestConsentQuery(int $teamId): Builder
    {
        return QuestionAnswer::query()
            ->select('question_answers.*')
            ->with('customer:id,full_name,email_address,contact_number,nationality_id,created_at,updated_at')
            ->whereNull('question_answers.deleted_at')
            ->whereIn('question_answers.answer', self::EXPLICIT_CONSENT_ANSWERS)
            ->whereHas('customer', fn (Builder $customer) => $customer->whereNull('deleted_at'))
            ->whereHas('question', fn (Builder $question) => $question
                ->where('slug', 'marketing_opt_in')
                ->whereNull('deleted_at'))
            ->whereHas('bookingItem', fn (Builder $item) => $item
                ->where('team_id', $teamId)
                ->whereNull('deleted_at'))
            ->whereNotExists(function ($newer) use ($teamId) {
                $newer->selectRaw('1')
                    ->from('question_answers as newer_answers')
                    ->join('questions as newer_questions', 'newer_questions.id', '=', 'newer_answers.question_id')
                    ->join('booking_items as newer_items', 'newer_items.id', '=', 'newer_answers.booking_item_id')
                    ->whereColumn('newer_answers.customer_id', 'question_answers.customer_id')
                    ->whereColumn('newer_answers.id', '>', 'question_answers.id')
                    ->where('newer_items.team_id', $teamId)
                    ->where('newer_questions.slug', 'marketing_opt_in')
                    ->whereIn('newer_answers.answer', self::EXPLICIT_CONSENT_ANSWERS)
                    ->whereNull('newer_answers.deleted_at')
                    ->whereNull('newer_questions.deleted_at')
                    ->whereNull('newer_items.deleted_at');
            });
    }

    private function whereAttended(
        Builder $query,
        int $teamId,
        CarbonImmutable $activityFrom,
        CarbonImmutable $activityTo,
    ): void {
        $query->whereExists(function ($attendance) use ($teamId, $activityFrom, $activityTo) {
            $attendance->selectRaw('1')
                ->from('customer_manifests as attended_manifests')
                ->join('booking_items as attended_items', 'attended_items.id', '=', 'attended_manifests.booking_item_id')
                ->join('bookings as attended_bookings', 'attended_bookings.id', '=', 'attended_items.booking_id')
                ->join('booking_inventories as attended_inventories', 'attended_inventories.booking_item_id', '=', 'attended_items.id')
                ->whereColumn('attended_manifests.customer_id', 'question_answers.customer_id')
                ->where('attended_manifests.team_id', $teamId)
                ->where('attended_items.team_id', $teamId)
                ->where('attended_bookings.team_id', $teamId)
                ->where('attended_manifests.is_indemnity', true)
                ->where('attended_manifests.attendance_status', 'arrived')
                ->whereDate('attended_inventories.date', '>=', $activityFrom->toDateString())
                ->whereDate('attended_inventories.date', '<=', $activityTo->toDateString())
                ->whereNull('attended_manifests.deleted_at')
                ->whereNull('attended_items.deleted_at')
                ->whereNull('attended_bookings.deleted_at')
                ->whereNull('attended_inventories.deleted_at')
                ->where(function ($operational) {
                    $operational->whereNull('attended_bookings.payment_window_expired_at')
                        ->orWhere('attended_bookings.status_payment_id', 2);
                });
        });
    }

    private function answersByCustomer(Collection $consents, int $teamId): array
    {
        $customerIds = $consents->pluck('customer_id')->unique()->values();
        if ($customerIds->isEmpty()) {
            return [];
        }

        return QuestionAnswer::query()
            ->select('question_answers.*')
            ->with('question:id,slug')
            ->join('booking_items as answer_items', 'answer_items.id', '=', 'question_answers.booking_item_id')
            ->where('answer_items.team_id', $teamId)
            ->whereIn('question_answers.customer_id', $customerIds)
            ->whereNull('question_answers.deleted_at')
            ->whereNull('answer_items.deleted_at')
            ->whereHas('question', fn (Builder $question) => $question
                ->whereIn('slug', self::CONTACT_ANSWER_SLUGS)
                ->whereNull('deleted_at'))
            ->orderBy('question_answers.id')
            ->get()
            ->groupBy('customer_id')
            ->map(fn (Collection $answers) => $answers
                ->groupBy(fn (QuestionAnswer $answer) => $answer->question?->slug)
                ->map(fn (Collection $slugAnswers) => $slugAnswers->last())
                ->filter())
            ->all();
    }

    private function latestAttendanceByCustomer(
        Collection $consents,
        int $teamId,
        CarbonImmutable $activityFrom,
        CarbonImmutable $activityTo,
    ): array {
        $customerIds = $consents->pluck('customer_id')->unique()->values();
        if ($customerIds->isEmpty()) {
            return [];
        }

        return DB::table('customer_manifests as manifests')
            ->join('booking_items as items', 'items.id', '=', 'manifests.booking_item_id')
            ->join('bookings', 'bookings.id', '=', 'items.booking_id')
            ->join('booking_inventories as inventories', 'inventories.booking_item_id', '=', 'items.id')
            ->where('manifests.team_id', $teamId)
            ->where('items.team_id', $teamId)
            ->where('bookings.team_id', $teamId)
            ->where('manifests.is_indemnity', true)
            ->where('manifests.attendance_status', 'arrived')
            ->whereIn('manifests.customer_id', $customerIds)
            ->whereDate('inventories.date', '>=', $activityFrom->toDateString())
            ->whereDate('inventories.date', '<=', $activityTo->toDateString())
            ->whereNull('manifests.deleted_at')
            ->whereNull('items.deleted_at')
            ->whereNull('bookings.deleted_at')
            ->whereNull('inventories.deleted_at')
            ->where(fn ($operational) => $operational
                ->whereNull('bookings.payment_window_expired_at')
                ->orWhere('bookings.status_payment_id', 2))
            ->select([
                'manifests.customer_id',
                'manifests.updated_at as manifest_updated_at',
                'inventories.date as activity_date',
                'bookings.id as booking_id',
                'bookings.reference as booking_reference',
            ])
            ->orderBy('inventories.date')
            ->orderBy('manifests.id')
            ->get()
            ->groupBy('customer_id')
            ->map(fn (Collection $rows) => $rows->last())
            ->all();
    }

    private function locations(array $answersByCustomer, Collection $consents): array
    {
        $answers = collect($answersByCustomer)->flatten(1);
        $countryIds = $answers
            ->filter(fn (QuestionAnswer $answer) => $answer->question?->slug === 'country_id')
            ->pluck('answer')
            ->merge($consents->pluck('customer.nationality_id'))
            ->filter(fn ($value) => filter_var($value, FILTER_VALIDATE_INT))
            ->map(fn ($value) => (int) $value)
            ->unique();
        $provinceIds = $answers
            ->filter(fn (QuestionAnswer $answer) => $answer->question?->slug === 'province_id')
            ->pluck('answer')
            ->filter(fn ($value) => filter_var($value, FILTER_VALIDATE_INT))
            ->map(fn ($value) => (int) $value)
            ->unique();

        return [
            Country::query()->whereIn('id', $countryIds)->get(['id', 'name', 'iso2'])->keyBy('id'),
            State::query()->whereIn('id', $provinceIds)->pluck('name', 'id'),
        ];
    }

    private function mapContact(
        QuestionAnswer $consent,
        Team $team,
        Collection $answers,
        $attendance,
        Collection $countries,
        Collection $provinces,
    ): array {
        $customer = $consent->customer;
        $fullName = trim((string) ($customer?->full_name ?: $this->answerValue($answers, 'full_name')));
        [$firstName, $lastName] = $this->splitName($fullName);
        $countryValue = $this->nullableString(
            $this->answerValue($answers, 'country_id') ?: $customer?->nationality_id,
        );
        $country = $countryValue && filter_var($countryValue, FILTER_VALIDATE_INT)
            ? $countries->get((int) $countryValue)
            : null;
        $provinceValue = $this->nullableString($this->answerValue($answers, 'province_id'));
        $updatedAt = collect([
            $consent->updated_at,
            $customer?->updated_at,
            $attendance?->manifest_updated_at
                ? CarbonImmutable::parse($attendance->manifest_updated_at)
                : null,
        ])->merge($answers->pluck('updated_at'))
            ->filter()
            ->sortByDesc(fn ($timestamp) => $timestamp->getTimestamp())
            ->first();

        return [
            'contact_id' => "team-{$team->id}-customer-{$consent->customer_id}",
            'customer_id' => $consent->customer_id,
            'booking_id' => $attendance?->booking_id,
            'booking_reference' => $attendance?->booking_reference,
            'last_activity_date' => $attendance?->activity_date,
            'site' => $team->name,
            'full_name' => $fullName ?: null,
            'first_name' => $firstName ?: null,
            'last_name' => $lastName ?: null,
            'email' => $this->nullableString($customer?->email_address ?: $this->answerValue($answers, 'email_address')),
            'mobile' => $this->nullableString($customer?->contact_number),
            'city' => null,
            'country_code' => $country?->iso2,
            'country' => $country?->name ?: ($country ? null : $countryValue),
            'province' => $provinceValue && filter_var($provinceValue, FILTER_VALIDATE_INT)
                ? $provinces->get((int) $provinceValue)
                : $provinceValue,
            'birthday_month' => $this->nullableInteger($this->answerValue($answers, 'birthday_month')),
            'birthday_day' => $this->nullableInteger($this->answerValue($answers, 'birthday_day')),
            'marketing_opt_in' => true,
            'consent_source' => 'check_in',
            'consent_at' => $this->isoTimestamp($consent->updated_at),
            'deleted' => false,
            'created_at' => $this->isoTimestamp($customer?->created_at ?: $consent->created_at),
            'updated_at' => $this->isoTimestamp($updatedAt),
        ];
    }

    private function answerValue(Collection $answers, string $slug): ?string
    {
        return $this->nullableString($answers->get($slug)?->answer);
    }

    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger($value): ?int
    {
        $value = $this->nullableString($value);

        return $value !== null && is_numeric($value) ? (int) $value : null;
    }

    private function isoTimestamp($timestamp): ?string
    {
        return $timestamp?->toImmutable()->utc()->toIso8601String();
    }
}
