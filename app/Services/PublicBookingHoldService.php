<?php

namespace App\Services;

use App\Models\InventoryTimeSlot;
use App\Models\PublicBookingHold;
use App\Models\Service;
use App\Models\Team;
use App\Services\Calendar\PublicServiceAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicBookingHoldService
{
    public function __construct(private readonly PublicServiceAvailability $availability)
    {
    }

    public function createOrRefresh(
        Team $team,
        Service $service,
        CarbonImmutable $date,
        int $slotId,
        int $adults,
        int $children,
        string $sessionId
    ): PublicBookingHold {
        return DB::transaction(function () use (
            $team,
            $service,
            $date,
            $slotId,
            $adults,
            $children,
            $sessionId
        ) {
            $logContext = [
                'team_id' => $team->id,
                'service_id' => $service->id,
                'date' => $date->toDateString(),
                'slot_id' => $slotId,
                'adults' => $adults,
                'children' => $children,
                'session_key' => substr(hash('sha256', $sessionId), 0, 12),
            ];
            $lockedSlot = InventoryTimeSlot::query()->whereKey($slotId)->lockForUpdate()->first();
            if (! $lockedSlot) {
                Log::warning('public_booking_hold.rejected_missing_slot', $logContext);
                throw ValidationException::withMessages([
                    'slot_id' => 'That time is no longer available. Please choose another time.',
                ]);
            }

            $calendar = $this->availability->build(
                $service,
                $date,
                $date,
                $adults,
                $children,
                $sessionId
            );
            $slot = collect($calendar['slots_by_date'][$date->toDateString()] ?? [])
                ->first(fn (array $candidate) => (int) $candidate['slot_id'] === $slotId);

            if (! $slot) {
                Log::warning('public_booking_hold.rejected_unavailable', [
                    ...$logContext,
                    'available_slots' => collect($calendar['slots_by_date'][$date->toDateString()] ?? [])
                        ->map(fn (array $candidate) => [
                            'slot_id' => (int) $candidate['slot_id'],
                            'time' => $candidate['time'],
                            'spaces_remaining' => (int) $candidate['spaces_remaining'],
                        ])
                        ->values()
                        ->all(),
                ]);
                throw ValidationException::withMessages([
                    'slot_id' => 'That time is no longer available. Please choose another time.',
                ]);
            }

            PublicBookingHold::query()
                ->active()
                ->where('session_id', $sessionId)
                ->where('team_id', $team->id)
                ->update(['cancelled_at' => now()]);

            $hold = PublicBookingHold::query()->create([
                'uuid' => (string) Str::uuid(),
                'session_id' => $sessionId,
                'team_id' => $team->id,
                'service_id' => $service->id,
                'season_id' => (int) $slot['season_id'],
                'inventory_id' => (int) $slot['inventory_id'],
                'inventory_time_slot_id' => (int) $slot['slot_id'],
                'booking_date' => $date->toDateString(),
                'adults' => $adults,
                'children' => $children,
                'expires_at' => now()->addMinutes((int) $team->frontend_hold_minutes),
            ]);

            return $hold;
        }, 3);
    }
}
