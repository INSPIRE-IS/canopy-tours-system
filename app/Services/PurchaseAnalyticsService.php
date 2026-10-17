<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\GiftVoucher;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class PurchaseAnalyticsService
{
    private const CONTEXT_TTL_HOURS = 24;

    public function issueContextToken(string $type, int $id): string
    {
        return Crypt::encryptString(json_encode([
            'purpose' => 'purchase_analytics',
            'type' => $type,
            'id' => $id,
            'expires_at' => now()->addHours(self::CONTEXT_TTL_HOURS)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    public function resolveContext(string $token): ?array
    {
        try {
            $context = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }

        if (($context['purpose'] ?? null) !== 'purchase_analytics'
            || ! in_array($context['type'] ?? null, ['booking', 'voucher'], true)
            || ! is_numeric($context['id'] ?? null)
            || (int) ($context['expires_at'] ?? 0) < now()->timestamp) {
            return null;
        }

        return [
            'type' => $context['type'],
            'id' => (int) $context['id'],
        ];
    }

    public function status(string $token): ?array
    {
        $context = $this->resolveContext($token);
        if (! $context) {
            return null;
        }

        if ($context['type'] === 'booking') {
            $booking = Booking::query()
                ->with([
                    'team',
                    'booking_items.booking_fee',
                    'booking_items.promotion',
                    'booking_items.service',
                ])
                ->find($context['id']);

            return $this->statusResult($context, $booking, fn () => $this->bookingPurchase($booking));
        }

        $voucher = GiftVoucher::query()
            ->with([
                'gift_voucher_items.team',
                'gift_voucher_items.service',
            ])
            ->find($context['id']);

        return $this->statusResult($context, $voucher, fn () => $this->voucherPurchase($voucher));
    }

    private function statusResult(array $context, $record, callable $purchase): array
    {
        if (! $record) {
            return [...$context, 'state' => 'not_found', 'purchase' => null];
        }

        if ((int) $record->status_payment_id !== 2) {
            return [...$context, 'state' => 'pending', 'purchase' => null];
        }

        return [...$context, 'state' => 'paid', 'purchase' => $purchase()];
    }

    private function bookingPurchase(Booking $booking): array
    {
        $affiliation = $booking->team?->name ?? 'Booking site';
        $items = [];
        $coupons = [];
        $activityValue = 0.0;

        foreach ($booking->booking_items as $bookingItem) {
            $serviceName = $bookingItem->service?->name ?? 'Activity';
            $activityTotal = $bookingItem->booking_fee?->tot_amount;
            if ($activityTotal === null) {
                $activityTotal = ((float) $bookingItem->price_adult * (int) $bookingItem->pax_adults)
                    + ((float) $bookingItem->price_child * (int) $bookingItem->pax_children)
                    + ((float) $bookingItem->price_guide * (int) $bookingItem->pax_guides);
            }
            $activityTotal = round((float) $activityTotal, 2);
            $activityValue += $activityTotal;

            $items[] = [
                'item_id' => 'service-'.$bookingItem->service_id,
                'item_name' => $serviceName,
                'affiliation' => $affiliation,
                'item_category' => 'Activity',
                'price' => $activityTotal,
                'quantity' => 1,
                'pax_adults' => (int) $bookingItem->pax_adults,
                'pax_children' => (int) $bookingItem->pax_children,
                'pax_guides' => (int) $bookingItem->pax_guides,
            ];

            if ($bookingItem->promotion?->promo_code) {
                $coupons[] = $bookingItem->promotion->promo_code;
            }
        }

        $ecommerce = [
            'transaction_id' => 'booking-'.($booking->reference ?: $booking->id),
            'value' => round($activityValue, 2),
            'currency' => 'ZAR',
            'items' => $items,
        ];

        $coupons = array_values(array_unique(array_filter($coupons)));
        if ($coupons) {
            $ecommerce['coupon'] = implode(', ', $coupons);
        }

        return ['event' => 'purchase', 'ecommerce' => $ecommerce];
    }

    private function voucherPurchase(GiftVoucher $voucher): array
    {
        $items = $voucher->gift_voucher_items->map(function ($voucherItem) {
            $affiliation = $voucherItem->team?->name ?? 'Booking site';
            $serviceName = $voucherItem->service?->name ?? 'Activity';

            return [
                'item_id' => 'gift-service-'.$voucherItem->service_id,
                'item_name' => $serviceName.' Gift Voucher',
                'affiliation' => $affiliation,
                'item_category' => 'Gift Voucher',
                'price' => round((float) $voucherItem->price_adult + (float) $voucherItem->price_child, 2),
                'quantity' => 1,
                'pax_adults' => (int) $voucherItem->pax_adults,
                'pax_children' => (int) $voucherItem->pax_children,
            ];
        })->values()->all();

        return [
            'event' => 'purchase',
            'ecommerce' => [
                'transaction_id' => 'gift-voucher-'.($voucher->voucher_code ?: $voucher->id),
                'value' => round((float) $voucher->grand_tot_due, 2),
                'currency' => 'ZAR',
                'items' => $items,
            ],
        ];
    }
}
