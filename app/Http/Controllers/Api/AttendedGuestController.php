<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Team;
use App\Services\AttendedGuestFeedService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AttendedGuestController extends Controller
{
    public function __invoke(Request $request, AttendedGuestFeedService $feed): JsonResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'min:1'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'cursor' => ['nullable', 'string', 'max:2048'],
        ]);

        $user = $request->user();

        abort_unless(
            $user?->currentAccessToken() instanceof PersonalAccessToken
            && $user->tokenCan(AttendedGuestFeedService::TOKEN_ABILITY)
            && $user->isIntegrationApiUser(),
            403,
        );
        $team = $user->integrationSites()->find((int) $validated['team_id']);
        abort_unless($team, 403, 'This integration account cannot access the requested site.');

        $pageSize = max(1, min(1000, (int) config('services.everlytic.api_page_size', 100)));

        $response = response()
            ->json($feed->snapshot(
                $team,
                CarbonImmutable::createFromFormat('Y-m-d', $validated['from'], 'Africa/Johannesburg'),
                CarbonImmutable::createFromFormat('Y-m-d', $validated['to'], 'Africa/Johannesburg'),
                $pageSize,
            ))
            ->header('Cache-Control', 'private, no-store');

        if (! isset($validated['cursor'])) {
            try {
                AuditLog::create([
                    'description' => 'everlytic:attended-guests.requested',
                    'user_id' => $user->id,
                    'subject_type' => Team::class,
                    'subject_id' => $team->id,
                    'host' => $request->ip(),
                    'properties' => [
                        'from' => $validated['from'],
                        'to' => $validated['to'],
                    ],
                ]);
            } catch (QueryException $exception) {
                Log::warning('Could not write the Everlytic request audit entry.', [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'from' => $validated['from'],
                    'to' => $validated['to'],
                    'database_error_code' => $exception->getCode(),
                ]);
            }
        }

        return $response;
    }
}
