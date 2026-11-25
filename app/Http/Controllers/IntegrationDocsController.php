<?php

namespace App\Http\Controllers;

use App\Services\AttendedGuestFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IntegrationDocsController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();
        $roleNames = $user?->roles()->pluck('name') ?? collect();
        $activeToken = $user?->tokens()
            ->where('name', 'like', 'everlytic-%')
            ->whereJsonContains('abilities', AttendedGuestFeedService::TOKEN_ABILITY)
            ->latest('id')
            ->first(['id', 'name', 'created_at', 'last_used_at', 'expires_at']);

        return view('integrations.everlytic-docs', [
            'siteName' => $user?->team?->name,
            'pageSize' => max(1, min(1000, (int) config('services.everlytic.api_page_size', 100))),
            'openApiUrl' => route('integration.everlytic.openapi'),
            'canSelfIssueToken' => $roleNames->count() === 1 && $roleNames->first() === 'Integration API',
            'activeToken' => $activeToken,
        ]);
    }

    public function issueToken(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password:web'],
        ]);

        $user = $request->user();
        $roleNames = $user?->roles()->pluck('name') ?? collect();
        abort_unless(
            $roleNames->count() === 1 && $roleNames->first() === 'Integration API',
            403,
        );
        abort_unless($user?->team_id && $user->team, 403, 'The integration user is not assigned to an active site.');

        if (config('sanctum.expiration') !== null) {
            throw ValidationException::withMessages([
                'password' => 'Token creation is unavailable while a global Sanctum expiry is configured.',
            ]);
        }

        $hasToken = $user->tokens()
            ->where('name', 'like', 'everlytic-%')
            ->whereJsonContains('abilities', AttendedGuestFeedService::TOKEN_ABILITY)
            ->exists();
        if ($hasToken) {
            throw ValidationException::withMessages([
                'password' => 'An active Everlytic token already exists. Ask an administrator to revoke it before creating a replacement.',
            ]);
        }

        $tokenName = 'everlytic-self-service-'.now()->format('Ymd-His');
        $token = $user->createToken($tokenName, [AttendedGuestFeedService::TOKEN_ABILITY]);

        return response()->json([
            'token' => $token->plainTextToken,
            'metadata' => [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'created_at' => $token->accessToken->created_at?->toIso8601String(),
                'last_used_at' => null,
                'expires_at' => null,
            ],
            'message' => 'Bearer token created. Copy it now because it cannot be displayed again.',
        ], 201)->header('Cache-Control', 'private, no-store');
    }

    public function openApi(): JsonResponse
    {
        $pageSize = max(1, min(1000, (int) config('services.everlytic.api_page_size', 100)));

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Canopy Tours Everlytic Attended Guest API',
                'version' => '1.0.0',
            ],
            'servers' => [['url' => url('/api/v1')]],
            'paths' => [
                '/attended-guests' => [
                    'get' => [
                        'summary' => 'Opted-in attended guests by inclusive activity-date range',
                        'security' => [['sanctumBearer' => []]],
                        'parameters' => [
                            ['name' => 'from', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'date']],
                            ['name' => 'to', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string', 'format' => 'date']],
                            ['name' => 'cursor', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            '200' => ['description' => "A cursor page containing up to {$pageSize} records"],
                            '401' => ['description' => 'Missing or invalid bearer token'],
                            '403' => ['description' => 'Token does not have the Everlytic read ability'],
                            '422' => ['description' => 'Invalid date range'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctumBearer' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
