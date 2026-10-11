<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Service;
use App\Models\Team;
use App\Models\PublicBookingHold;
use App\Services\Calendar\PublicServiceAvailability;
use App\Services\PublicBookingHoldService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingV2Controller extends Controller
{
    public function entry(): RedirectResponse
    {
        $publishedTeamIds = Team::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->limit(2)
            ->pluck('id');

        if ($publishedTeamIds->count() === 1) {
            return redirect()->route('front.welcome', ['team' => $publishedTeamIds->first()]);
        }

        // Keep the existing selector as a safe fallback until the new multi-site entry screen is built.
        return redirect()->route('front.legacy.welcome');
    }

    public function index(Request $request, Team $team): Response
    {
        abort_unless($team->is_published, 404);

        $services = Service::query()
            ->with([
                'file',
                'servicefieldValues.field',
                'seasons' => fn ($query) => $query
                    ->where('internal_only', false)
                    ->whereHas('inventory')
                    ->orderBy('start_date'),
                'seasons.inventory',
            ])
            ->where('team_id', $team->id)
            ->where('is_online_bookable', true)
            ->whereHas('seasons', fn ($query) => $query
                ->where('internal_only', false)
                ->whereHas('inventory'))
            ->orderBy('name')
            ->get()
            ->map(function (Service $service) {
                $fieldValues = $service->servicefieldValues->mapWithKeys(function ($fieldValue) {
                    return [$fieldValue->field->name => $this->frontContent($fieldValue->value)];
                });
                $today = CarbonImmutable::today('Africa/Johannesburg');
                $initialSeason = $service->seasons
                    ->first(fn ($season) => CarbonImmutable::parse($season->end_date)->gte($today))
                    ?? $service->seasons->last();
                $initialDate = $initialSeason
                    ? $today->max(CarbonImmutable::parse($initialSeason->start_date))->toDateString()
                    : $today->toDateString();

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'tag' => $service->tag,
                    'capacity' => (int) $service->capacity,
                    'pillbox_conditions' => $service->pillbox_conditions,
                    'arrival_label' => $this->arrivalLabel($service->pillbox_conditions),
                    'arrival_minutes' => $this->arrivalMinutes($service->pillbox_conditions),
                    'image' => $this->publicAssetUrl($service->file?->path),
                    'initial_date' => $initialDate,
                    'from_price' => $service->seasons->min('rake_rate_adult'),
                    'gift_activity_id' => $initialSeason?->id,
                    'description' => $fieldValues['Description'] ?? null,
                    'activity_description' => $fieldValues['Activity Description'] ?? null,
                    'hero_summary' => $this->plainContent(
                        $fieldValues['Description'] ?? $fieldValues['Activity Description'] ?? null
                    ),
                    'age_label' => $this->minimumAgeLabel(
                        $fieldValues['Condition Text'] ?? $fieldValues['Requirements'] ?? null
                    ),
                    'arrangements' => $fieldValues['Arrangements'] ?? null,
                    'requirements' => $fieldValues['Requirements'] ?? null,
                    'inclusions' => $fieldValues['Inclusions'] ?? null,
                    'exclusions' => $fieldValues['Exclusions'] ?? null,
                    'condition_text' => $fieldValues['Condition Text'] ?? null,
                    'notes' => $fieldValues['Notes'] ?? null,
                ];
            })
            ->values();

        $selectedServiceId = (int) $request->integer('service_id');
        if (! $services->contains('id', $selectedServiceId)) {
            $selectedServiceId = (int) ($services->first()['id'] ?? 0);
        }

        $team->load(['file_logo', 'file_image']);

        return Inertia::render('PublicBookingV2/Book', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'color' => $team->color,
                'file_logo' => $team->file_logo ? [
                    'path' => $this->publicAssetUrl($team->file_logo->path),
                    'file_name' => $team->file_logo->file_name,
                ] : null,
                'file_image' => $team->file_image ? [
                    'path' => $this->publicAssetUrl($team->file_image->path),
                    'file_name' => $team->file_image->file_name,
                ] : null,
            ],
            'services' => $services,
            'selectedServiceId' => $selectedServiceId,
            'notice' => $request->string('notice')->toString() === 'hold_expired'
                ? 'Your checkout hold expired. Please choose a time again.'
                : null,
        ]);
    }

    public function availability(
        Request $request,
        Service $service,
        PublicServiceAvailability $availability
    ): JsonResponse {
        abort_unless(
            $service->is_online_bookable
                && $service->team
                && $service->team->is_published,
            404
        );

        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'adults' => ['nullable', 'integer', 'min:0'],
            'children' => ['nullable', 'integer', 'min:0'],
        ]);

        $startDate = CarbonImmutable::parse($validated['start_date'])->startOfDay();
        $endDate = CarbonImmutable::parse($validated['end_date'])->startOfDay();

        if ($startDate->diffInDays($endDate) > 62) {
            return response()->json([
                'message' => 'Availability can be requested for a maximum of 63 days at a time.',
            ], 422);
        }

        return response()->json($availability->build(
            $service,
            $startDate,
            $endDate,
            (int) ($validated['adults'] ?? 0),
            (int) ($validated['children'] ?? 0),
            $this->publicHoldSessionKey($request)
        ));
    }

    public function storeHold(
        Request $request,
        Team $team,
        Service $service,
        PublicBookingHoldService $holdService
    ): RedirectResponse {
        $this->abortUnlessPublicService($team, $service);

        $validated = $this->validateSelection($request);
        $date = CarbonImmutable::parse($validated['date'], 'Africa/Johannesburg')->startOfDay();
        $adults = (int) $validated['adults'];
        $children = (int) ($validated['children'] ?? 0);
        $this->validateSelectionLimits($service, $date, $adults, $children);

        if (! $team->frontend_hold_enabled) {
            return redirect()->route('front.v2.checkout', [
                'team' => $team->id,
                'service' => $service->id,
                ...$validated,
            ]);
        }

        $hold = $holdService->createOrRefresh(
            $team,
            $service,
            $date,
            (int) $validated['slot_id'],
            $adults,
            $children,
            $this->publicHoldSessionKey($request)
        );
        $request->session()->put('public_booking_v2_hold_uuid', $hold->uuid);

        return redirect()->route('front.v2.checkout', [
            'team' => $team->id,
            'service' => $service->id,
            'hold' => $hold->uuid,
        ]);
    }

    public function checkout(
        Request $request,
        Team $team,
        Service $service,
        PublicServiceAvailability $availability
    ): Response|RedirectResponse {
        $this->abortUnlessPublicService($team, $service);

        $hold = null;
        if ($team->frontend_hold_enabled) {
            $request->validate(['hold' => ['required', 'uuid']]);
            $hold = PublicBookingHold::query()
                ->active()
                ->where('uuid', $request->string('hold'))
                ->where('session_id', $this->publicHoldSessionKey($request))
                ->where('team_id', $team->id)
                ->where('service_id', $service->id)
                ->first();

            if (! $hold) {
                return redirect()->route('front.welcome', [
                    'team' => $team->id,
                    'service_id' => $service->id,
                    'notice' => 'hold_expired',
                ]);
            }

            $date = CarbonImmutable::parse($hold->booking_date, 'Africa/Johannesburg')->startOfDay();
            $adults = (int) $hold->adults;
            $children = (int) $hold->children;
            $slotId = (int) $hold->inventory_time_slot_id;
        } else {
            $validated = $this->validateSelection($request);
            $date = CarbonImmutable::parse($validated['date'], 'Africa/Johannesburg')->startOfDay();
            $adults = (int) $validated['adults'];
            $children = (int) ($validated['children'] ?? 0);
            $slotId = (int) $validated['slot_id'];
        }

        $pax = $adults + $children;
        $this->validateSelectionLimits($service, $date, $adults, $children);

        $calendar = $availability->build(
            $service,
            $date,
            $date,
            $adults,
            $children,
            $hold?->session_id
        );
        $slot = collect($calendar['slots_by_date'][$date->toDateString()] ?? [])
            ->first(fn (array $candidate) => (int) $candidate['slot_id'] === $slotId);

        if (! $slot) {
            throw ValidationException::withMessages([
                'slot_id' => 'That time is no longer available. Please choose another time.',
            ]);
        }

        $adultTotal = round($adults * (float) $slot['adult_price'], 2);
        $childTotal = round($children * (float) $slot['child_price'], 2);
        $season = Season::query()
            ->with(['extras' => fn ($query) => $query->where('is_active', true)])
            ->whereKey((int) $slot['season_id'])
            ->where('service_id', $service->id)
            ->where('team_id', $team->id)
            ->where('internal_only', false)
            ->firstOrFail();
        $extras = $season->include_public_extras
            ? $season->extras->map(function ($extra) use ($pax) {
                $quantity = $extra->is_per_guest ? $pax : 1;

                return [
                    'id' => $extra->id,
                    'name' => $extra->name,
                    'description' => $extra->description,
                    'unit_price' => (float) $extra->unit_price,
                    'is_per_guest' => (bool) $extra->is_per_guest,
                    'quantity' => $quantity,
                    'total' => round($quantity * (float) $extra->unit_price, 2),
                ];
            })->values()
            : collect();

        $team->load(['file_logo', 'file_image']);
        $service->loadMissing(['file', 'servicefieldValues.field']);
        $fieldValues = $service->servicefieldValues->mapWithKeys(function ($fieldValue) {
            return [$fieldValue->field->name => $this->frontContent($fieldValue->value)];
        });

        return Inertia::render('PublicBookingV2/Checkout', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'file_logo' => $team->file_logo ? [
                    'path' => $this->publicAssetUrl($team->file_logo->path),
                ] : null,
            ],
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'image' => $this->publicAssetUrl($service->file?->path)
                    ?? $this->publicAssetUrl($team->file_image?->path),
                'condition_text' => $fieldValues['Condition Text'] ?? null,
            ],
            'selection' => [
                'date' => $date->toDateString(),
                'time' => $slot['time'],
                'arrival_by' => $slot['arrival_by'],
                'adults' => $adults,
                'children' => $children,
                'season_id' => (int) $slot['season_id'],
                'inventory_id' => (int) $slot['inventory_id'],
                'slot_id' => (int) $slot['slot_id'],
                'spaces_remaining' => (int) $slot['spaces_remaining'],
            ],
            'quote' => [
                'adult_unit_price' => (float) $slot['adult_price'],
                'adult_total' => $adultTotal,
                'child_unit_price' => (float) $slot['child_price'],
                'child_total' => $childTotal,
                'total' => round($adultTotal + $childTotal, 2),
            ],
            'extras' => $extras,
            'hold' => $hold ? [
                'uuid' => $hold->uuid,
                'expires_at' => $hold->expires_at->toIso8601String(),
                'duration_minutes' => (int) $team->frontend_hold_minutes,
            ] : null,
            'changeUrl' => route('front.welcome', [
                'team' => $team->id,
                'service_id' => $service->id,
            ]),
        ]);
    }

    public function releaseHold(
        Request $request,
        Team $team,
        Service $service,
        string $hold
    ): RedirectResponse {
        $this->abortUnlessPublicService($team, $service);

        PublicBookingHold::query()
            ->where('uuid', $hold)
            ->where('session_id', $this->publicHoldSessionKey($request))
            ->where('team_id', $team->id)
            ->where('service_id', $service->id)
            ->whereNull('completed_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);

        if ($request->session()->get('public_booking_v2_hold_uuid') === $hold) {
            $request->session()->forget('public_booking_v2_hold_uuid');
        }

        return redirect()->route('front.welcome', [
            'team' => $team->id,
            'service_id' => $service->id,
            'notice' => 'hold_expired',
        ]);
    }

    private function validateSelection(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'slot_id' => ['required', 'integer', 'min:1'],
            'adults' => ['required', 'integer', 'min:1'],
            'children' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function validateSelectionLimits(
        Service $service,
        CarbonImmutable $date,
        int $adults,
        int $children
    ): void {
        if ($date->lt(CarbonImmutable::today('Africa/Johannesburg'))) {
            throw ValidationException::withMessages([
                'date' => 'Please choose a current or future booking date.',
            ]);
        }

        if (($adults + $children) > (int) $service->capacity) {
            throw ValidationException::withMessages([
                'adults' => "This activity allows a maximum of {$service->capacity} guests per booking.",
            ]);
        }
    }

    private function abortUnlessPublicService(Team $team, Service $service): void
    {
        abort_unless(
            $team->is_published
                && $service->team_id === $team->id
                && $service->is_online_bookable,
            404
        );
    }

    private function publicHoldSessionKey(Request $request): string
    {
        $key = $request->session()->get('public_booking_v2_session_key');

        if (! is_string($key) || $key === '') {
            $key = (string) Str::uuid();
            $request->session()->put('public_booking_v2_session_key', $key);
        }

        return $key;
    }

    private function frontContent(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $plainText = trim(strip_tags(html_entity_decode($value)));

        return $plainText === '' || $plainText === '.' ? null : $value;
    }

    private function publicAssetUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && (str_starts_with($path, '/storage/') || str_starts_with($path, '/images/'))) {
            return $path;
        }

        return $url;
    }

    private function plainContent(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $plainText = preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($value)));
        $plainText = trim((string) $plainText);

        return $plainText === '' || $plainText === '.'
            ? null
            : Str::ucfirst(Str::lower($plainText));
    }

    private function arrivalLabel(?string $conditions): ?string
    {
        $minutes = $this->arrivalMinutes($conditions);
        if ($minutes === null) {
            return null;
        }

        return sprintf('Arrive %s min early', $minutes);
    }

    private function arrivalMinutes(?string $conditions): ?int
    {
        if (! $conditions || ! preg_match('/arrive\s+(\d+)\s+minutes?/i', $conditions, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function minimumAgeLabel(?string $content): ?string
    {
        $plainText = $this->plainContent($content);
        if (! $plainText || ! preg_match('/children\s+from\s+(\d+)\s+years?/i', $plainText, $matches)) {
            return null;
        }

        return sprintf('Ages %s+', $matches[1]);
    }
}
