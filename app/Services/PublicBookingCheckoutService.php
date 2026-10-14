<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingExtra;
use App\Models\BookingFee;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingPriceLine;
use App\Models\BookingTermsAcceptance;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\InventoryTimeSlot;
use App\Models\InventoryTypeDay;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\LookupItem;
use App\Models\Promotion;
use App\Models\PublicBookingHold;
use App\Models\Season;
use App\Models\SeasonExtra;
use App\Models\Service;
use App\Models\Team;
use App\Models\TypeInvoice;
use App\Services\Calendar\PublicServiceAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicBookingCheckoutService
{
    public function __construct(
        private readonly PublicServiceAvailability $availability,
        private readonly PricingService $pricing
    ) {
    }

    public function createProvisionalBooking(
        Team $team,
        Service $service,
        array $payload,
        string $holdSessionKey,
        string $browserSessionId,
        string $termsSnapshot,
        string $ipAddress,
        ?string $userAgent
    ): Booking {
        return DB::transaction(function () use (
            $team,
            $service,
            $payload,
            $holdSessionKey,
            $browserSessionId,
            $termsSnapshot,
            $ipAddress,
            $userAgent
        ) {
            $hold = $this->resolveAndLockHold($team, $service, $payload, $holdSessionKey);
            [$date, $slotId, $adults, $children] = $this->selection($payload, $hold);

            if (($adults + $children) < 1) {
                throw ValidationException::withMessages([
                    'adults' => 'Please select at least one adult or child.',
                ]);
            }
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

            InventoryTimeSlot::query()->whereKey($slotId)->lockForUpdate()->firstOrFail();

            $calendar = $this->availability->build(
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

            $season = Season::query()
                ->whereKey((int) $slot['season_id'])
                ->where('team_id', $team->id)
                ->where('service_id', $service->id)
                ->where('internal_only', false)
                ->firstOrFail();

            $promoCode = trim((string) ($payload['promo_code'] ?? '')) ?: null;
            $promotion = $this->validatedPromotion($promoCode, $service, $season, $date, $slotId);
            try {
                $pricing = $this->pricing->calculate([
                    'service_id' => $service->id,
                    'season_id' => $season->id,
                    'booking_date' => $date->toDateString(),
                    'promo_code' => $promotion?->promo_code,
                    'pax_groups' => array_values(array_filter([
                        $adults > 0 ? ['type' => 'adult', 'count' => $adults, 'discount_id' => null] : null,
                        $children > 0 ? ['type' => 'child', 'count' => $children, 'discount_id' => null] : null,
                    ])),
                ]);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'promo_code' => $exception->getMessage(),
                ]);
            }

            if ($promoCode && empty($pricing['applied_promotion'])) {
                throw ValidationException::withMessages([
                    'promo_code' => 'That promo code is no longer valid for this booking.',
                ]);
            }

            $extras = $this->resolveExtras($payload['extra_ids'] ?? [], $season, $adults + $children);
            $activityTotal = round((float) $pricing['total_price'], 2);
            $extrasTotal = round((float) $extras->sum('total_amount'), 2);
            $grandTotal = round($activityTotal + $extrasTotal, 2);

            $customer = Customer::create([
                'email_address' => trim($payload['email']),
                'full_name' => trim($payload['full_name']),
                'contact_number' => trim($payload['phone']),
            ]);
            $booking = Booking::create([
                'user_id' => null,
                'session_id' => $browserSessionId,
                'lead_name' => $customer->full_name,
                'type_booking_id' => 1,
                'status_payment_id' => 1,
                'payment_window_expired_at' => null,
                'is_sms' => 0,
                'is_corporate' => 0,
                'due_date' => $date->toDateString(),
                'tot_paid' => 0,
                'grand_tot_due' => $grandTotal,
                'reference' => 'REF-' . strtoupper(Str::random(6)),
                'customer_id' => $customer->id,
                'team_id' => $team->id,
            ]);

            $adultTotal = round((float) ($pricing['totals_by_type']['adult'] ?? 0), 2);
            $childTotal = round((float) ($pricing['totals_by_type']['child'] ?? 0), 2);
            $bookingItem = BookingItem::create([
                'service_id' => $service->id,
                'season_id' => $season->id,
                'inventory_id' => (int) $slot['inventory_id'],
                'booking_id' => $booking->id,
                'price_adult' => (float) ($pricing['applied_rates']['adult'] ?? 0),
                'price_child' => (float) ($pricing['applied_rates']['child'] ?? 0),
                'price_guide' => 0,
                'promotion_id' => $pricing['applied_promotion']['id'] ?? null,
                'pax_adults' => $adults,
                'pax_children' => $children,
                'pax_guides' => 0,
                'team_id' => $team->id,
            ]);

            $bookingItem->customers()->sync([
                $customer->id => ['team_id' => $team->id, 'is_lead' => 1],
            ]);
            CustomerManifest::create([
                'customer_id' => $customer->id,
                'booking_item_id' => $bookingItem->id,
                'team_id' => $team->id,
                'is_indemnity' => 0,
                'type' => 'adult',
                'position' => '',
                'notes' => '',
            ]);
            $bookingDetail = BookingDetail::create([
                'booking_item_id' => $bookingItem->id,
                'is_wholesaler_booking' => 0,
                'is_needs_pickup' => 0,
                'is_web_booking' => 1,
                'is_videos_photos' => 0,
                'is_exclusive_resource_use' => 0,
                'is_override_time_slots' => 0,
                'team_id' => $team->id,
            ]);
            $bookingInventory = BookingInventory::create([
                'booking_item_id' => $bookingItem->id,
                'booking_detail_id' => $bookingDetail->id,
                'date' => $date->toDateString(),
                'adults' => $adults,
                'children' => $children,
                'guides' => 0,
                'pax' => $adults + $children,
                'inventory_type_day_id' => $this->inventoryTypeDayId((int) $slot['inventory_id'], $date),
                'inventory_time_slot_id' => $slotId,
                'inventory_override_id' => null,
                'team_id' => $team->id,
            ]);
            $bookingFee = BookingFee::create([
                'booking_item_id' => $bookingItem->id,
                'booking_detail_id' => $bookingDetail->id,
                'tot_adults' => $adultTotal,
                'tot_children' => $childTotal,
                'tot_guides' => 0,
                'is_voucher' => $bookingItem->promotion_id !== null,
                'voucher_code' => $bookingItem->promotion_id,
                'invoice_note' => '',
                'is_email_reminder' => 0,
                'booking_note' => '',
                'tot_amount' => $activityTotal,
                'tot_paid' => 0,
                'team_id' => $team->id,
            ]);

            $this->storePriceLines($bookingItem, $pricing, $date, $slot['time']);
            $this->storeExtras($booking, $bookingItem, $extras);
            $this->storeInvoice($booking, $bookingInventory, $season, $adultTotal, $childTotal, $grandTotal);

            $acceptedAt = now();
            $termsHash = hash('sha256', $termsSnapshot);
            BookingTermsAcceptance::create([
                'booking_id' => $booking->id,
                'team_id' => $team->id,
                'accepted_at' => $acceptedAt,
                'wording_snapshot' => $termsSnapshot,
                'wording_hash' => $termsHash,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
            AuditLog::create([
                'description' => 'Public booking terms accepted',
                'subject_id' => $booking->id,
                'subject_type' => Booking::class,
                'user_id' => null,
                'properties' => [
                    'terms_hash' => $termsHash,
                    'accepted_at' => $acceptedAt->toIso8601String(),
                    'hold_uuid' => $hold?->uuid,
                ],
                'host' => $ipAddress,
            ]);

            if ($hold) {
                $hold->update([
                    'booking_id' => $booking->id,
                    'completed_at' => now(),
                ]);
            }

            return $booking->load('booking_items.booking_fee', 'booking_extras', 'terms_acceptance');
        }, 3);
    }

    private function resolveAndLockHold(
        Team $team,
        Service $service,
        array $payload,
        string $sessionKey
    ): ?PublicBookingHold {
        $holdUuid = trim((string) ($payload['hold_uuid'] ?? ''));
        if ($holdUuid === '') {
            if ($team->frontend_hold_enabled) {
                throw ValidationException::withMessages([
                    'hold_uuid' => 'Your checkout hold has expired. Please choose a time again.',
                ]);
            }

            return null;
        }

        $hold = PublicBookingHold::query()
            ->where('uuid', $holdUuid)
            ->where('session_id', $sessionKey)
            ->where('team_id', $team->id)
            ->where('service_id', $service->id)
            ->lockForUpdate()
            ->first();

        if (! $hold || $hold->completed_at || $hold->cancelled_at || $hold->expires_at->lte(now())) {
            throw ValidationException::withMessages([
                'hold_uuid' => 'Your checkout hold has expired. Please choose a time again.',
            ]);
        }

        return $hold;
    }

    private function selection(array $payload, ?PublicBookingHold $hold): array
    {
        $date = CarbonImmutable::parse($hold?->booking_date ?? $payload['date'], 'Africa/Johannesburg')->startOfDay();
        $adults = (int) ($hold?->adults ?? $payload['adults']);
        $children = (int) ($hold?->children ?? ($payload['children'] ?? 0));
        $slotId = (int) ($hold?->inventory_time_slot_id ?? $payload['slot_id']);

        return [$date, $slotId, $adults, $children];
    }

    private function validatedPromotion(
        ?string $promoCode,
        Service $service,
        Season $season,
        CarbonImmutable $date,
        int $slotId
    ): ?Promotion {
        if (! $promoCode) {
            return null;
        }

        $promotion = Promotion::query()
            ->where('promo_code', $promoCode)
            ->where('service_id', $service->id)
            ->where('internal_use_only', false)
            ->first();
        if (! $promotion) {
            throw ValidationException::withMessages(['promo_code' => 'That promo code is invalid.']);
        }

        $slot = InventoryTimeSlot::find($slotId);
        $result = $promotion->validateCode(
            $date->toDateString(),
            $date->dayOfWeekIso,
            $promotion->promo_day_time_slots->isNotEmpty() ? $slot : null,
            $season->id
        );
        if (! ($result['status'] ?? false)) {
            throw ValidationException::withMessages([
                'promo_code' => $result['message'] ?? 'That promo code is not valid for this booking.',
            ]);
        }

        return $promotion;
    }

    private function resolveExtras(array $ids, Season $season, int $pax)
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }
        if (! $season->include_public_extras) {
            throw ValidationException::withMessages(['extra_ids' => 'Extras are no longer available for this activity.']);
        }

        $extras = SeasonExtra::query()
            ->whereIn('id', $ids)
            ->where('season_id', $season->id)
            ->where('team_id', $season->team_id)
            ->where('is_active', true)
            ->get();
        if ($extras->count() !== $ids->count()) {
            throw ValidationException::withMessages(['extra_ids' => 'One of the selected extras is no longer available.']);
        }

        return $extras->map(function (SeasonExtra $extra) use ($pax) {
            $quantity = $extra->is_per_guest ? $pax : 1;

            return [
                'season_extra_id' => $extra->id,
                'name' => $extra->name,
                'description' => $extra->description,
                'quantity' => $quantity,
                'unit_price' => round((float) $extra->unit_price, 2),
                'total_amount' => round($quantity * (float) $extra->unit_price, 2),
            ];
        });
    }

    private function inventoryTypeDayId(int $inventoryId, CarbonImmutable $date): int
    {
        $id = InventoryTypeDay::query()
            ->where('inventory_id', $inventoryId)
            ->where('type_day_id', $date->dayOfWeekIso)
            ->value('id');
        if (! $id) {
            throw ValidationException::withMessages(['date' => 'That date is no longer available.']);
        }

        return (int) $id;
    }

    private function storePriceLines(
        BookingItem $bookingItem,
        array $pricing,
        CarbonImmutable $date,
        string $time
    ): void {
        foreach (($pricing['line_items'] ?? []) as $line) {
            $count = (int) ($line['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $baseRate = round((float) ($line['base_rate'] ?? 0), 2);
            $finalRate = round((float) ($line['final_rate'] ?? $baseRate), 2);
            $label = $line['adjustment_label'] ?? null;
            BookingPriceLine::create([
                'booking_id' => $bookingItem->booking_id,
                'booking_item_id' => $bookingItem->id,
                'team_id' => $bookingItem->team_id,
                'activity_date' => $date->toDateString(),
                'activity_time' => $time,
                'source_type' => $bookingItem->promotion_id && $label ? 'promotion' : 'rack',
                'source_id' => $bookingItem->promotion_id && $label ? $bookingItem->promotion_id : null,
                'pax_type' => $line['type'] ?? 'adult',
                'count' => $count,
                'base_rate' => $baseRate,
                'final_rate' => $finalRate,
                'line_total' => round((float) ($line['line_total'] ?? ($finalRate * $count)), 2),
                'label' => $label,
                'meta' => [],
            ]);
        }
    }

    private function storeExtras(Booking $booking, BookingItem $bookingItem, $extras): void
    {
        foreach ($extras as $extra) {
            BookingExtra::create([
                'booking_id' => $booking->id,
                'booking_item_id' => $bookingItem->id,
                'service_id' => $bookingItem->service_id,
                'season_extra_id' => $extra['season_extra_id'],
                'team_id' => $booking->team_id,
                'type' => BookingExtra::TYPE_CUSTOM,
                'name' => $extra['name'],
                'description' => $extra['description'],
                'quantity' => $extra['quantity'],
                'unit_price' => $extra['unit_price'],
                'total_amount' => $extra['total_amount'],
                'exclude_from_franchise_fees' => true,
                'meta' => [
                    'source' => 'public_booking_v2',
                    'pricing_note' => 'Included in the public PayFast checkout total.',
                ],
            ]);
        }
    }

    private function storeInvoice(
        Booking $booking,
        BookingInventory $bookingInventory,
        Season $season,
        float $adultTotal,
        float $childTotal,
        float $grandTotal
    ): void {
        $invoiceTypeId = TypeInvoice::where('slug', 'std')->value('id');
        $template = InvoiceTemplate::query()
            ->where('team_id', $booking->team_id)
            ->where('is_default', true)
            ->when($invoiceTypeId, fn ($query) => $query->where('type_invoice_id', $invoiceTypeId))
            ->first();
        $vatMultiplier = LookupItem::findDefaultForTeam('vat_rate', $booking->team_id)?->multiplier ?? 1.15;
        $subtotal = round($grandTotal / $vatMultiplier, 2);
        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'gift_voucher_id' => null,
            'invoice_date' => $booking->getRawOriginal('due_date'),
            'due_date' => $booking->getRawOriginal('due_date'),
            'subtotal' => $subtotal,
            'vat' => round($grandTotal - $subtotal, 2),
            'total' => $grandTotal,
            'status' => 'draft',
            'invoice_template_id' => $template?->id,
            'type_invoice_id' => $invoiceTypeId,
            'team_id' => $booking->team_id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'pax_adults' => $bookingInventory->adults,
            'pax_children' => $bookingInventory->children,
            'pax_guides' => 0,
            'price_adult' => $adultTotal,
            'price_child' => $childTotal,
            'price_guide' => 0,
            'team_id' => $booking->team_id,
            'service_id' => $season->service_id,
            'season_id' => $season->id,
            'booking_inventory_id' => $bookingInventory->id,
        ]);
    }
}
