<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\MultiTenantModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class BookingItem extends Model
{
    use HasFactory, MultiTenantModelTrait, SoftDeletes, Auditable;

    public $table = 'booking_items';

    protected $fillable = [
        'booking_id',
        'service_id',
        'season_id',
        'inventory_id',
        'pax_adults',
        'pax_children',
        'pax_guides',
        'price_adult',
        'price_child',
        'price_guide',
        'promotion_id',
        'wholesaler_id',
        'discount_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'team_id',
    ];

    // protected function serializeDate(DateTimeInterface $date)
    // {
    //     return $date->format('Y-m-d H:i:s');
    // }
    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    // public function customers()
    // {
    //     //return $this->belongsToMany(Customer::class);
    //     return $this->belongsToMany(Customer::class)
    //         ->withPivot('team_id', 'is_lead')
    //         ->withTimestamps();
    // }

    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'booking_items_customer', 'booking_item_id', 'customer_id')
            ->withPivot(['team_id', 'is_lead'])->withTimestamps();
    }

    public function booking_detail()
    {
        return $this->hasOne(BookingDetail::class, 'booking_item_id', 'id');
    }

    public function booking_fee()
    {
        return $this->hasOne(BookingFee::class, 'booking_item_id', 'id');
    }

    // public function booking_payments()
    // {
    //     return $this->hasMany(BookingPayment::class);
    // }

    public function booking_inventory()
    {
        return $this->hasOne(BookingInventory::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function season()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function customer_manifests()
    {
        return $this->hasMany(CustomerManifest::class);
    }

    public function booking_staff()
    {
        return $this->hasMany(BookingStaff::class);
    }

    public function booking_infos()
    {
        return $this->hasMany(BookingInfo::class);
    }

    public function booking_fees()
    {
        return $this->hasMany(BookingFee::class);
    }

    public function booking_questionnaire()
    {
        return $this->hasOne(BookingQuestionnaire::class);
    }

    public function applyDiscountIfAvailable()
    {
        if ($this->type !== 'adult') {
            return;
        }

        $booking = $this->booking;
        $availableDiscounts = $booking->booking_discounts()
            ->whereDoesntHave('customer_discounts') // only unused discounts
            ->get();

        foreach ($availableDiscounts as $discount) {
            if ($discount->isValidFor($this)) {
                // link it
                $this->discounts()->create([
                    'booking_discount_id' => $discount->id,
                    'applied_at' => now(),
                ]);
                break;
            }
        }
    }

    public function paxByBookingItem($bookingItemId, bool $excludeGuides = true)
    {
        return $this->paxWithCustomers($excludeGuides)
            ->filter(fn($pax) => $pax['booking_item_id'] === $bookingItemId)
            ->values();
    }


    public function paxWithCustomers(bool $excludeGuides = true)
    {

        $paxList = collect();
        $vatMultiplier = LookupItem::where('lookup_id', 1)->where('is_default', true)->first()->multiplier;
        // ensure totals are ints
        $totals = [
            'adult' => intval($this->pax_adults ?? 0),
            'child' => intval($this->pax_children ?? 0),
            'guide' => intval($this->pax_guides ?? 0),
        ];

        // If excluding guides, set guides to zero (don't unset keys)
        if ($excludeGuides) {
            $totals['guide'] = 0;
        }

        //logger()->info('**********pax totals*************** ', [$totals]);

        // Gather discount items for this booking (flatten BookingDiscount -> BookingDiscountItems)
        $discountItems = collect();
        if ($this->booking && method_exists($this->booking, 'booking_discounts')) {
            $discountItems = $this->booking->booking_discounts
                ->flatMap(fn($bd) => $bd->booking_discount_items ?? collect())
                ->filter()
                ->values();
        }

        if ($this->promotion_id != null) {
            $booking_inventory_date = $this->booking_inventory->date;

            $discount = $this->calcDiscount($this->service_id, $this->season_id,  $this->wholesaler_id, $this->promotion_id,  $booking_inventory_date, $this->booking_date, $this->pax_adults, $this->pax_children);
            Log::info('discount ' . var_export($discount, true));
        }

        $unassignedDiscounts = $discountItems->groupBy('pax_type')->map(fn($col) => $col->values());

        // Track how many customers are already linked per type
        $linkedCounts = [
            'adult' => 0,
            'child' => 0,
            'guide' => 0,
        ];

        $tot_paid = $this->booking_fee->tot_paid; // / $vatMultiplier;
        $tot_amount = $this->booking_fee->tot_amount;
        // 1) Add actual customers first
        foreach ($this->customers as $customer) {
            $paxType = $this->guessPaxType($customer) ?? 'adult';
            logger()->info('**********pax type****existing*********** ', [$paxType]);
            // If excluding guides, skip guide customers entirely
            if ($excludeGuides && $paxType === 'guide') {
                continue;
            }

            $manifest = $this->manifestForCustomer($customer->id);

            // increment linked count for this type
            $linkedCounts[$paxType]++;

            // see if this customer already has a discount explicitly linked
            $customerDiscount = null;
            if (method_exists($customer, 'booking_discount_items')) {
                $customerDiscount = $customer->booking_discount_items
                    ->first(fn($bdi) => ($bdi->pax_type ?? null) === $paxType);
            }

            // if not, allocate one from unassigned pool
            if (!$customerDiscount && isset($unassignedDiscounts[$paxType]) && $unassignedDiscounts[$paxType]->isNotEmpty()) {
                $customerDiscount = $unassignedDiscounts[$paxType]->shift();
            }
            $type = $manifest?->type ?? $paxType;


            if ($this->promotion_id != null) {
                if ($type == 'adult') {
                    $price = $discount['applied_rates']['adult'];
                    $paid = $tot_paid >= $price ? $price : 0;
                    $tot_paid = $tot_paid - $price;
                }
                if ($type == 'child') {
                    $price = $discount['applied_rates']['child'];
                    $paid = $tot_paid >= $price ? $price : 0;
                    $tot_paid = $tot_paid - $price;
                }
            } else {
                $price = $customerDiscount->final_rate ?? $this->defaultRate($paxType);
                $paid = $tot_paid >= $price ? $price : 0;
                $tot_paid = $tot_paid - $price;
            }
            //$paid  = $customer->pivot->paid ?? 0;
            $bookingItemId = $customer->pivot->booking_item_id ?? null;
            $paxList->push([
                'reference' => $this->booking->reference ?? null,
                'booking_item_id'  => $bookingItemId,
                'type'              => $type,
                'customer_id'       => $customer->id,
                'full_name'         => $customer->full_name,
                'email_address'     => $customer->email_address,
                'contact_number'    => $customer->contact_number,
                'age'               => $customer->age ?? null,
                'parent_id'         => $customer->parent_id ?? null,
                'notes'             =>  $manifest?->notes ?? null,
                'is_indemnity'      =>  $manifest?->is_indemnity ?? null,
                'position'          => $manifest?->position ?? null,
                'price'     => $price,
                'paid'      => $paid,
                'due'       => $price - $paid,
                'files' => $this->booking->files,
                'booking_discount_item_id'  => $discount->discount->id ?? null,
                'discount_id'  => $discount->discount->id ?? null,
                'discount'  => $customerDiscount->discount->name ?? null,
                'is_lead'   => $customer->pivot->is_lead,
                'team_id'   => $customer->pivot->team_id ?? null,
                'source'    => 'customer',
            ]);

            logger()->info('pushed customer pax', [
                'type' => $paxType,
                'linkedCounts' => $linkedCounts,
            ]);
        }

        //logger()->info('after customers linkedCounts', [$linkedCounts, 'totals' => $totals]);

        // 2) Add placeholders for remaining pax slots (per type)
        foreach (['adult', 'child', 'guide'] as $type) {
            // respect excludeGuides
            if ($excludeGuides && $type === 'guide') {
                continue;
            }

            $remaining = max(0, ($totals[$type] ?? 0) - ($linkedCounts[$type] ?? 0));

            for ($i = 0; $i < $remaining; $i++) {


                $discount = null;
                if (isset($unassignedDiscounts[$type]) && $unassignedDiscounts[$type]->isNotEmpty()) {
                    $discount = $unassignedDiscounts[$type]->shift();
                    //logger()->info(' unassigned discount', [$unassignedDiscounts[$type]]);
                }

                //logger()->info(' customers discount', [$discount]);
                //logger()->info(' customers type', [$type]);
                $price = $discount->final_rate ?? $this->defaultRate($type);
                //logger()->info(' customers price', [$price]);
                $paid = $tot_paid >= $price ? $price : 0;
                $tot_paid = $tot_paid - $price;

                logger()->info('**********pax type****new*********** ', [$type]);
                $paxList->push([
                    'reference' => $this->booking->reference ?? null,
                    'booking_item_id'  => $bookingItemId,
                    'type'      => $type,
                    'customer_id' => null,
                    'full_name'      => null,
                    'email_address'     => null,
                    'contact_number'     => null,
                    'age'       => null,
                    'parent_id'     => null,
                    'notes'     => null,
                    'is_indemnity'     => null,
                    'price'     => $price,
                    'paid'      => $paid,
                    'due'       => $price - $paid,
                    'discount_id'  => $discount->discount->id ?? null,
                    'discount'  => $discount->discount->name ?? null,
                    'is_lead'   => false,
                    'team_id'   => null,
                    'source'    => 'placeholder',
                ]);
            }

            //logger()->info("placeholders for {$type}", ['remaining' => $remaining]);
        }

        return $paxList->values();
    }

    protected function defaultRate($paxType)
    {
        return match ($paxType) {
            'adult'  => $this->price_adult,
            'child'  => $this->price_child,
            'guide'  => $this->price_guide,
            default  => 0,
        };
    }

    public function manifestForCustomer($customerId)
    {
        return $this->customer_manifests()->where('customer_id', $customerId)->first();
    }

    protected function discountsFor($paxType)
    {
        // get booking discounts if any
        $discounts = $this->booking
            ->booking_discounts()
            ->with('booking_discount_items.discount')
            ->first();

        if (!$discounts) {
            return [];
        }

        return $discounts->booking_discount_items
            ->where('pax_type', $paxType)
            ->map(fn($item) => [
                'discount' => $item->discount->name,
                'base_rate' => $item->base_rate,
                'final_rate' => $item->final_rate,
            ])
            ->values();
    }

    protected function guessPaxType(Customer $customer)
    {
        return $customer->parent_id == null ? 'adult' : 'child'; // $customer->age >= 18 ? 'adult' : 'child';
    }

    public function calcDiscount($service_id, $season_id, $wholesaler_id, $promotion_id, $activityDate, $booking_date, $pax_adults, $pax_children)
    {
        //logger()->info([$service_id, $season_id, $wholesaler_id, $promotion_id, $activityDate, $booking_date, $pax_adults, $pax_children]);
        $season = Season::findOrFail($season_id);
        $seasonAdult = $season->price_adult ?? $season->rake_rate_adult ?? 0;
        $seasonChild = $season->price_child ?? $season->rake_rate_child ?? 0;
        $seasonGuide = $season->price_guide ?? $season->rake_rate_guide ?? 0;

        // Step 1: Base rack rates
        $rates = [
            'adult' => (float) $seasonAdult,
            'child' => (float) $seasonChild,
            'guide' => (float) $seasonGuide,
        ];
        logger()->info('season rates ' . var_export($rates, true));
        // Step 2: Override with wholesaler rates (if present)
        if (!empty($data['wholesaler_id'])) {
            $wr = WholesalerRate::where('wholesaler_id', $wholesaler_id)
                ->where('season_id', $season->id)
                ->first();
            if ($wr) {
                $rates['adult'] = $wr->price_adult ?? $rates['adult'];
                $rates['child'] = $wr->price_child ?? $rates['child'];
                $rates['guide'] = $wr->price_guide ?? $rates['guide'];
            }
            logger()->info('wholesaler rates ' . var_export($rates, true));
        }
        //dd($activityDate);
        // Keep a copy of base (post-wholesaler, pre-promo)
        $baseRates = $rates;

        $appliedPromo = null;
        $appliedDiscounts = [];
        $total = 0.0;

        // Step 3: Check if promotion is applied (mutually exclusive with discounts)
        if (!empty($promotion_id)) {

            // $promo = $query->first();
            $today = now()->toDateString();
            $promo = Promotion::where('id', $promotion_id)
                ->where('service_id', $service_id)
                // Booking period check (today is when booking is made)
                ->whereDate('booking_start_date', '<=', $today)
                ->whereDate('booking_end_date', '>=', $today)
                // Activity period check (customer’s chosen date)
                ->whereDate('activity_start_date', '<=', $activityDate)
                ->whereDate('activity_end_date', '>=', $activityDate)
                ->first();


            //dd($promo);
            if ($promo) {
                logger()->info('promo ' . $promo->id);
                // Normalize params (supports casted array or raw JSON string)
                $rawParams = $promo->params;
                if (is_string($rawParams)) {
                    $decoded = json_decode($rawParams, true);
                    $params = is_array($decoded) ? $decoded : [];
                } elseif (is_array($rawParams)) {
                    $params = $rawParams;
                } else {
                    $params = [];
                }
                logger()->info('params ' . var_export($params, true));

                // $totalPax  = collect($data['pax_groups'])->sum('count');
                $totalPax  = $pax_adults + $pax_children;

                // Apply promo rules
                switch ($promo->type) {
                    case 'percent':
                        foreach ($rates as $k => $v) {
                            $rates[$k] = round($v - ($v * ($promo->value / 100)), 2);
                        }
                        logger()->info('percent promo applied ' . var_export($rates, true));
                        break;

                    case 'fixed_value':
                        $rates['adult'] = round(max(0, $rates['adult'] - (float) $promo->value), 2);
                        logger()->info('promo applied ' . var_export($rates, true));
                        break;

                    case 'graduated':
                        // Support BOTH:
                        // (a) map:  {"lookup":{"1":"300","2":"300",...}}
                        // (b) array:[{"pax":1,"value":300}, ...]
                        $paxRate = null;
                        if (!empty($params['lookup']) && is_array($params['lookup'])) {
                            // (a) assoc map
                            $key = (string) $totalPax;
                            if (array_key_exists($key, $params['lookup'])) {
                                $paxRate = (float) $params['lookup'][$key];
                            }
                        } elseif (!empty($params['lookup']) && is_string($params['lookup'])) {
                            // If stored as JSON string inside params (rare), decode it.
                            $lk = json_decode($params['lookup'], true) ?: [];
                            $key = (string) $totalPax;
                            if (is_array($lk) && array_key_exists($key, $lk)) {
                                $paxRate = (float) $lk[$key];
                            }
                        } elseif (!empty($params['lookup']) && is_object($params['lookup'])) {
                            $lk = (array) $params['lookup'];
                            $key = (string) $totalPax;
                            if (array_key_exists($key, $lk)) {
                                $paxRate = (float) $lk[$key];
                            }
                        } elseif (!empty($params['lookup']) && is_iterable($params['lookup'])) {
                            // (b) array of objects
                            foreach ($params['lookup'] as $row) {
                                if (isset($row['pax']) && (int)$row['pax'] === (int)$totalPax) {
                                    $paxRate = (float) ($row['value'] ?? 0);
                                    break;
                                }
                            }
                        }
                        logger()->info('paxRate **************** ' . $paxRate);

                        if ($paxRate !== null) {
                            $rates['adult'] = $paxRate;
                            $rates['child'] = $paxRate;
                            //$rates['guide'] = $promoTotal;

                            return response()->json([
                                'base_rates'         => $baseRates,
                                'applied_rates'      => $rates, // unchanged per-pax; total is overridden
                                'applied_promotion'  => $appliedPromo,
                                'applied_discounts'  => [],
                                'total_price'        => round($paxRate * $totalPax, 2),
                            ]);
                        }
                        // If there’s no exact lookup, fall through to normal per-pax multiplication.
                        break;

                    case 'varied_child':
                        // Support single rule  {min_adults, child_discount_percent}
                        // and tiered rules     {tiers:[{min_adults, child_discount_percent}, ...]}
                        if (!empty($params['tiers']) && is_array($params['tiers'])) {
                            // choose best tier with min_adults <= adultCount
                            $best = collect($params['tiers'])
                                ->filter(fn($t) => isset($t['min_adults']) && $pax_adults >= (int)$t['min_adults'])
                                ->sortByDesc('min_adults')
                                ->first();

                            if ($best && isset($best['child_discount_percent'])) {
                                $pct = (float) $best['child_discount_percent'];
                                $rates['child'] = round($rates['child'] - ($rates['child'] * ($pct / 100)), 2);
                            }
                        } elseif (isset($params['min_adults'], $params['child_discount_percent'])) {
                            if ($pax_adults >= (int)$params['min_adults']) {
                                $pct = (float) $params['child_discount_percent'];
                                $rates['child'] = round($rates['child'] - ($rates['child'] * ($pct / 100)), 2);
                            }
                        }
                        logger()->info('varied_child promo applied ' . var_export($rates, true));
                        break;

                    case 'volume_discount':
                        // pick best tier where totalPax >= pax
                        $best = null;
                        if (!empty($params['tiers']) && is_array($params['tiers'])) {
                            $best = collect($params['tiers'])
                                ->filter(fn($t) => isset($t['pax']) && $totalPax >= (int)$t['pax'])
                                ->sortByDesc('pax')
                                ->first();
                        }
                        if ($best) {
                            if (($best['type'] ?? '') === 'percent') {
                                $pct = (float) ($best['value'] ?? 0);
                                foreach ($rates as $k => $v) {
                                    $rates[$k] = round($v - ($v * ($pct / 100)), 2);
                                }
                            } elseif (($best['type'] ?? '') === 'fixed_value') {
                                $fv = (float) ($best['value'] ?? 0);
                                $rates['adult'] = round(max(0, $rates['adult'] - $fv), 2);
                            }
                        }
                        logger()->info('volume_discount promo applied ' . var_export($rates, true));
                        break;

                    case 'time_slot':
                        $rates['adult'] = round(max(0, $rates['adult'] - ($rates['adult'] * ($promo->value / 100))), 2);
                        $rates['child'] = round(max(0, $rates['child'] - ($rates['child'] * ($promo->value / 100))), 2);
                        logger()->info('time_slot promo applied ' . var_export($rates, true));
                        break;
                }
            }
        }

        $result = [
            'base_rates'         => $baseRates,         // post-wholesaler, pre-promo
            'applied_rates'      => $rates,             // post-promo (or == base if no promo)
        ];
        // dd($result);
        return $result;
    }
}
