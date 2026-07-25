<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Promotion;
use App\Models\Season;
use App\Models\WholesalerRate;

class PricingService
{
    public function calculate(array $data): array
    {
        $season = Season::findOrFail($data['season_id']);
        $paxGroups = $this->buildPaxGroups($data);

        $overrideRates = (bool) ($data['override_rates'] ?? false);
        $overrideValues = [
            'adult' => (float) ($data['override_rate_adult'] ?? $data['rate_per_adult'] ?? 0),
            'child' => (float) ($data['override_rate_child'] ?? $data['rate_per_child'] ?? 0),
            'guide' => (float) ($data['override_rate_guide'] ?? $data['rate_per_guide'] ?? 0),
        ];

        $baseRates = [
            'adult' => (float) ($season->price_adult ?? $season->rake_rate_adult ?? 0),
            'child' => (float) ($season->price_child ?? $season->rake_rate_child ?? 0),
            'guide' => (float) ($season->price_guide ?? $season->rake_rate_guide ?? 0),
        ];
        $rates = $baseRates;

        $appliedPromo = null;
        $appliedDiscounts = [];
        $forcedTotal = null;
        $promoLineItems = [];

        if ($overrideRates) {
            $rates = $overrideValues;
            $baseRates = $overrideValues;
            $paxGroups = array_map(function ($group) {
                $group['discount_id'] = null;
                return $group;
            }, $paxGroups);
            $data['wholesaler_id'] = null;
            $data['promo_code'] = null;
            $data['promotion_id'] = null;
            $data['invoice_discount_id'] = null;
        } else {
            if (!empty($data['wholesaler_id'])) {
                $wr = WholesalerRate::where('wholesaler_id', $data['wholesaler_id'])
                    ->where('season_id', $season->id)
                    ->where('service_id', $data['service_id'])
                    ->when(!empty($data['team_id']), fn ($query) => $query->where('team_id', $data['team_id']))
                    ->first();
                if ($wr) {
                    $rates['adult'] = $wr->price_adult ?? $rates['adult'];
                    $rates['child'] = $wr->price_child ?? $rates['child'];
                    $rates['guide'] = $wr->price_guide ?? $rates['guide'];
                }
            }

            $baseRates = $rates;

            if (!empty($data['wholesaler_id'])) {
                $data['promo_code'] = null;
                $data['promotion_id'] = null;
                $data['invoice_discount_id'] = null;
                $paxGroups = array_map(function ($group) {
                    $group['discount_id'] = null;
                    return $group;
                }, $paxGroups);
            }
            if (!empty($data['promo_code']) || !empty($data['promotion_id'])) {
                $data['invoice_discount_id'] = null;
                $paxGroups = array_map(function ($group) {
                    $group['discount_id'] = null;
                    return $group;
                }, $paxGroups);
            }
            if (!empty($data['invoice_discount_id'])) {
                $paxGroups = array_map(function ($group) {
                    $group['discount_id'] = null;
                    return $group;
                }, $paxGroups);
            }

            if (!empty($data['promo_code']) || !empty($data['promotion_id'])) {
                $today = now()->toDateString();
                $activityDate = $data['booking_date'] ?? $today;

                $promo = null;
                if (!empty($data['promotion_id'])) {
                    $promo = Promotion::where('id', $data['promotion_id'])
                        ->where('service_id', $data['service_id'])
                        ->first();
                    if ($promo && $promo->activity_start_date && $promo->activity_end_date) {
                        if ($activityDate < $promo->activity_start_date || $activityDate > $promo->activity_end_date) {
                            $promo = null;
                        }
                    }
                }

                if (!$promo && !empty($data['promo_code'])) {
                    $promo = Promotion::where('promo_code', $data['promo_code'])
                        ->where('service_id', $data['service_id'])
                        ->whereDate('booking_start_date', '<=', $today)
                        ->whereDate('booking_end_date', '>=', $today)
                        ->whereDate('activity_start_date', '<=', $activityDate)
                        ->whereDate('activity_end_date', '>=', $activityDate)
                        ->first();
                }

                if ($promo) {
                    $appliedPromo = [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'type' => $promo->type,
                        'promo_code' => $promo->promo_code,
                        'value' => (float) $promo->value,
                        'params' => $promo->params,
                        'booking_start_date' => $promo->booking_start_date,
                        'booking_end_date' => $promo->booking_end_date,
                        'activity_start_date' => $promo->activity_start_date,
                        'activity_end_date' => $promo->activity_end_date,
                        'internal_use_only' => (bool) $promo->internal_use_only,
                    ];

                    $rawParams = $promo->params;
                    if (is_string($rawParams)) {
                        $decoded = json_decode($rawParams, true);
                        $params = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($rawParams)) {
                        $params = $rawParams;
                    } else {
                        $params = [];
                    }

                    $adultCount = collect($paxGroups)->where('type', 'adult')->sum('count');
                    $childCount = collect($paxGroups)->where('type', 'child')->sum('count');
                    $guideCount = collect($paxGroups)->where('type', 'guide')->sum('count');
                    $totalPax = $adultCount + $childCount;
                    $baseTotal = ($baseRates['adult'] * $adultCount)
                        + ($baseRates['child'] * $childCount)
                        + ($baseRates['guide'] * $guideCount);
                    $applyBookingTotal = function (float $targetTotal) use (&$rates, $baseRates, $baseTotal) {
                        if ($baseTotal <= 0) {
                            return;
                        }
                        $multiplier = max(0, $targetTotal) / $baseTotal;
                        $rates['adult'] = round($baseRates['adult'] * $multiplier, 2);
                        $rates['child'] = round($baseRates['child'] * $multiplier, 2);
                        $rates['guide'] = round($baseRates['guide'] * $multiplier, 2);
                    };
                    $typeCounts = [
                        'adult' => (int) $adultCount,
                        'child' => (int) $childCount,
                    ];
                    $typeLabels = [
                        'adult' => 'adults',
                        'child' => 'children',
                    ];
                    $applyFixedToType = function (string $type, float $discount, ?int $targetCount = null) use (&$rates, &$promoLineItems, $baseRates, $typeCounts, $typeLabels, $appliedPromo) {
                        $count = (int) ($typeCounts[$type] ?? 0);
                        if ($count <= 0) {
                            throw new \InvalidArgumentException("This promo applies to {$typeLabels[$type]}, but no {$typeLabels[$type]} have been added.");
                        }

                        $baseRate = (float) ($baseRates[$type] ?? 0);
                        $discountedCount = min(max(1, (int) ($targetCount ?? $count)), $count);
                        $discountCap = $baseRate * $discountedCount;
                        $appliedDiscountValue = min($discount, $discountCap);
                        $discountedRate = round(max(0, $baseRate - ($appliedDiscountValue / $discountedCount)), 2);

                        $subtotal = $baseRate * $count;
                        $targetSubtotal = max(0, $subtotal - $appliedDiscountValue);
                        $rates[$type] = round($targetSubtotal / $count, 2);

                        $promoLineItems[$type] = [];
                        $rackCount = $count - $discountedCount;
                        if ($rackCount > 0) {
                            $promoLineItems[$type][] = [
                                'type' => $type,
                                'count' => $rackCount,
                                'discount_id' => null,
                                'base_rate' => $baseRate,
                                'final_rate' => $baseRate,
                                'line_total' => round($baseRate * $rackCount, 2),
                                'adjustment_label' => null,
                            ];
                        }
                        $promoLineItems[$type][] = [
                            'type' => $type,
                            'count' => $discountedCount,
                            'discount_id' => null,
                            'base_rate' => $baseRate,
                            'final_rate' => $discountedRate,
                            'line_total' => round($discountedRate * $discountedCount, 2),
                            'adjustment_label' => $appliedPromo['name'] ?? 'Promotion',
                        ];
                    };

                    switch ($promo->type) {
                        case 'percent':
                            foreach ($rates as $key => $value) {
                                $rates[$key] = round($value - ($value * ($promo->value / 100)), 2);
                            }
                            break;
                        case 'time_slot':
                            foreach ($rates as $key => $value) {
                                $rates[$key] = round($value - ($value * ($promo->value / 100)), 2);
                            }
                            break;
                        case 'fixed_value':
                            $mode = (string) ($params['fixed_value_mode'] ?? 'booking');
                            $discount = (float) $promo->value;
                            switch ($mode) {
                                case 'one_adult':
                                    $applyFixedToType('adult', $discount, 1);
                                    break;
                                case 'one_child':
                                    $applyFixedToType('child', $discount, 1);
                                    break;
                                case 'each_adult':
                                    $applyFixedToType('adult', $discount * $adultCount, (int) $adultCount);
                                    break;
                                case 'each_child':
                                    $applyFixedToType('child', $discount * $childCount, (int) $childCount);
                                    break;
                                case 'each_adult_child':
                                    if ($totalPax <= 0) {
                                        throw new \InvalidArgumentException('This promo applies to adults and children, but no adult or child pax have been added.');
                                    }
                                    if ($adultCount > 0) {
                                        $applyFixedToType('adult', $discount * $adultCount, (int) $adultCount);
                                    }
                                    if ($childCount > 0) {
                                        $applyFixedToType('child', $discount * $childCount, (int) $childCount);
                                    }
                                    break;
                                case 'per_pax':
                                    // Legacy mode: keep historic booking-total behaviour for existing records.
                                    $targetTotal = max(0, $baseTotal - ($discount * $totalPax));
                                    $applyBookingTotal($targetTotal);
                                    break;
                                case 'booking':
                                default:
                                    $targetTotal = max(0, $baseTotal - $discount);
                                    $applyBookingTotal($targetTotal);
                                    break;
                            }
                            break;
                        case 'graduated':
                            $paxRate = null;
                            if (!empty($params['lookup']) && is_array($params['lookup'])) {
                                $key = (string) $totalPax;
                                if (array_key_exists($key, $params['lookup'])) {
                                    $paxRate = (float) $params['lookup'][$key];
                                }
                            } elseif (!empty($params['lookup']) && is_string($params['lookup'])) {
                                $lookup = json_decode($params['lookup'], true) ?: [];
                                $key = (string) $totalPax;
                                if (is_array($lookup) && array_key_exists($key, $lookup)) {
                                    $paxRate = (float) $lookup[$key];
                                }
                            } elseif (!empty($params['lookup']) && is_object($params['lookup'])) {
                                $lookup = (array) $params['lookup'];
                                $key = (string) $totalPax;
                                if (array_key_exists($key, $lookup)) {
                                    $paxRate = (float) $lookup[$key];
                                }
                            } elseif (!empty($params['lookup']) && is_iterable($params['lookup'])) {
                                foreach ($params['lookup'] as $row) {
                                    if (isset($row['pax']) && (int) $row['pax'] === (int) $totalPax) {
                                        $paxRate = (float) ($row['value'] ?? 0);
                                        break;
                                    }
                                }
                            }

                            if ($paxRate !== null) {
                                $applyBookingTotal($paxRate);
                                $forcedTotal = (float) $paxRate;
                            }
                            break;
                        case 'varied_child':
                            if (!empty($params['tiers']) && is_array($params['tiers'])) {
                                $best = collect($params['tiers'])
                                    ->filter(fn($tier) => isset($tier['min_adults']) && $adultCount >= (int) $tier['min_adults'])
                                    ->sortByDesc('min_adults')
                                    ->first();

                                if ($best && isset($best['child_discount_percent'])) {
                                    $pct = (float) $best['child_discount_percent'];
                                    $rates['child'] = round($rates['child'] - ($rates['child'] * ($pct / 100)), 2);
                                }
                            } elseif (isset($params['min_adults'], $params['child_discount_percent'])) {
                                if ($adultCount >= (int) $params['min_adults']) {
                                    $pct = (float) $params['child_discount_percent'];
                                    $rates['child'] = round($rates['child'] - ($rates['child'] * ($pct / 100)), 2);
                                }
                            }
                            break;
                        case 'volume_discount':
                            $best = null;
                            if (!empty($params['tiers']) && is_array($params['tiers'])) {
                                $best = collect($params['tiers'])
                                    ->filter(fn($tier) => isset($tier['pax']) && $totalPax >= (int) $tier['pax'])
                                    ->sortByDesc('pax')
                                    ->first();
                            }
                            if ($best) {
                                if (($best['type'] ?? '') === 'percent') {
                                    $pct = (float) ($best['value'] ?? 0);
                                    foreach ($rates as $key => $value) {
                                        $rates[$key] = round($value - ($value * ($pct / 100)), 2);
                                    }
                                } elseif (($best['type'] ?? '') === 'fixed_value') {
                                    $targetTotal = max(0, $baseTotal - (float) ($best['value'] ?? 0));
                                    $applyBookingTotal($targetTotal);
                                }
                            }
                            break;
                    }
                }
            }
        }

        $invoiceDiscount = null;
        $invoiceMultiplier = 1;
        if (!empty($data['invoice_discount_id'])) {
            $invoiceDiscount = Discount::find($data['invoice_discount_id']);
            if ($invoiceDiscount) {
                $invoiceMultiplier = 1 - ((float) $invoiceDiscount->percent_off / 100);
            }
        }

        $lineItems = [];
        $totalsByType = [
            'adult' => 0.0,
            'child' => 0.0,
            'guide' => 0.0,
        ];
        $handledPromoLineTypes = [];
        foreach ($paxGroups as $group) {
            $type = $group['type'] ?? null;
            if (!isset($totalsByType[$type])) {
                continue;
            }
            $count = (int) ($group['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }

            if ($appliedPromo && isset($promoLineItems[$type]) && !isset($handledPromoLineTypes[$type])) {
                foreach ($promoLineItems[$type] as $promoLineItem) {
                    $lineItems[] = $promoLineItem;
                    $totalsByType[$type] = round($totalsByType[$type] + ($promoLineItem['final_rate'] * $promoLineItem['count']), 2);
                }
                $handledPromoLineTypes[$type] = true;
                continue;
            }

            $baseRate = (float) ($baseRates[$type] ?? 0);
            $finalRate = (float) ($appliedPromo ? ($rates[$type] ?? $baseRate) : $baseRate);

            if (!$appliedPromo && !$invoiceDiscount && !empty($group['discount_id'])) {
                $discount = Discount::find($group['discount_id']);
                if ($discount) {
                    $finalRate = round($baseRate - ($baseRate * ((float) $discount->percent_off / 100)), 2);
                    $appliedDiscounts[] = [
                        'id' => $discount->id,
                        'name' => $discount->name,
                        'percent_off' => (float) $discount->percent_off,
                        'applied_to' => $type,
                        'count' => $count,
                        'final_rate' => $finalRate,
                    ];
                }
            }

            if ($invoiceDiscount) {
                $finalRate = round($finalRate * $invoiceMultiplier, 2);
            }

            $lineItems[] = [
                'type' => $type,
                'count' => $count,
                'discount_id' => $group['discount_id'] ?? null,
                'base_rate' => $baseRate,
                'final_rate' => $finalRate,
                'line_total' => round($finalRate * $count, 2),
            ];
            $totalsByType[$type] = round($totalsByType[$type] + ($finalRate * $count), 2);
        }

        $total = round($totalsByType['adult'] + $totalsByType['child'] + $totalsByType['guide'], 2);
        if ($forcedTotal !== null) {
            $delta = round($forcedTotal - $total, 2);
            if (abs($delta) > 0) {
                if ($totalsByType['adult'] > 0) {
                    $totalsByType['adult'] = round($totalsByType['adult'] + $delta, 2);
                } elseif ($totalsByType['child'] > 0) {
                    $totalsByType['child'] = round($totalsByType['child'] + $delta, 2);
                } else {
                    $totalsByType['guide'] = round($totalsByType['guide'] + $delta, 2);
                }
            }
            $total = round($forcedTotal, 2);
        }

        if ($invoiceDiscount) {
            $appliedDiscounts[] = [
                'id' => $invoiceDiscount->id,
                'name' => $invoiceDiscount->name,
                'percent_off' => (float) $invoiceDiscount->percent_off,
                'applied_to' => 'invoice',
                'count' => null,
                'final_rate' => null,
            ];
        }

        $discountLines = array_values(array_map(function ($item) {
            if (empty($item['discount_id'])) {
                return null;
            }
            return [
                'type' => $item['type'],
                'count' => $item['count'],
                'discount_id' => $item['discount_id'],
                'base_rate' => $item['base_rate'],
                'final_rate' => $item['final_rate'],
            ];
        }, $lineItems));
        $discountLines = array_values(array_filter($discountLines));

        return [
            'base_rates' => $baseRates,
            'applied_rates' => $rates,
            'applied_promotion' => $appliedPromo,
            'applied_discounts' => $appliedDiscounts,
            'total_price' => $total,
            'line_items' => $lineItems,
            'totals_by_type' => $totalsByType,
            'discount_lines' => $discountLines,
            'pax_groups' => $paxGroups,
        ];
    }

    protected function buildPaxGroups(array $data): array
    {
        if (!empty($data['pax_groups']) && is_array($data['pax_groups'])) {
            return array_values(array_filter(array_map(function ($group) {
                if (!isset($group['type'])) {
                    return null;
                }
                return [
                    'type' => $group['type'],
                    'count' => (int) ($group['count'] ?? 0),
                    'discount_id' => $group['discount_id'] ?? null,
                ];
            }, $data['pax_groups'])));
        }

        $adults = (int) ($data['adults'] ?? 0);
        $children = (int) ($data['children'] ?? 0);
        $guides = (int) ($data['guides'] ?? 0);
        $groups = [];
        if ($adults > 0) {
            $groups[] = ['type' => 'adult', 'count' => $adults, 'discount_id' => null];
        }
        if ($children > 0) {
            $groups[] = ['type' => 'child', 'count' => $children, 'discount_id' => null];
        }
        if ($guides > 0) {
            $groups[] = ['type' => 'guide', 'count' => $guides, 'discount_id' => null];
        }

        $lines = $data['discount_lines'] ?? [];
        if (!is_array($lines) || empty($lines)) {
            return $groups;
        }

        $remaining = [
            'adult' => $adults,
            'child' => $children,
            'guide' => $guides,
        ];
        $withDiscounts = [];
        foreach ($lines as $line) {
            $type = $line['type'] ?? null;
            if (!isset($remaining[$type])) {
                continue;
            }
            $count = (int) ($line['count'] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $count = min($count, $remaining[$type]);
            if ($count <= 0) {
                continue;
            }
            $remaining[$type] -= $count;
            $withDiscounts[] = [
                'type' => $type,
                'count' => $count,
                'discount_id' => $line['discount_id'] ?? null,
            ];
        }

        foreach ($remaining as $type => $count) {
            if ($count > 0) {
                $withDiscounts[] = [
                    'type' => $type,
                    'count' => $count,
                    'discount_id' => null,
                ];
            }
        }

        return $withDiscounts;
    }
}
