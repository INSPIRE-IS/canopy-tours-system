<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Team;
use App\Services\Calendar\PublicServiceAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicBookingV2Controller extends Controller
{
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
        if (!$services->contains('id', $selectedServiceId)) {
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
            (int) ($validated['children'] ?? 0)
        ));
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
        if (!$url) {
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
        if (!$conditions || !preg_match('/arrive\s+(\d+)\s+minutes?/i', $conditions, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function minimumAgeLabel(?string $content): ?string
    {
        $plainText = $this->plainContent($content);
        if (!$plainText || !preg_match('/children\s+from\s+(\d+)\s+years?/i', $plainText, $matches)) {
            return null;
        }

        return sprintf('Ages %s+', $matches[1]);
    }
}
