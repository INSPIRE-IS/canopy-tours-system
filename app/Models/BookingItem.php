<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingItem extends Model
{
    use HasFactory, SoftDeletes, Auditable;

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
        // 'team_id',
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
            ->withPivot(['team_id', 'is_lead']);
    }

    public function booking_detail()
    {
        return $this->hasOne(BookingDetail::class);
    }

    public function booking_fee()
    {
        return $this->hasOne(BookingFee::class);
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
            logger()->info('**********pax type*************** ', [$paxType]);
            // If excluding guides, skip guide customers entirely
            if ($excludeGuides && $paxType === 'guide') {
                continue;
            }

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

            $price = $customerDiscount->final_rate ?? $this->defaultRate($paxType);
            $paid = $tot_paid >= $price ? $price : 0;
            $tot_paid = $tot_paid - $price;
            //$paid  = $customer->pivot->paid ?? 0;
            $bookingItemId = $customer->pivot->booking_item_id ?? null;
            $paxList->push([
                'reference' => $this->booking->reference ?? null,
                'booking_item_id'  => $bookingItemId,
                'type'      => $paxType,
                'customer_id'      => $customer->id,
                'full_name'      => $customer->full_name,
                'email_address'     => $customer->email_address,
                'contact_number'     => $customer->contact_number,
                'age'       => $customer->age ?? null,
                'notes'     => optional($customer->manifest->first())->notes ?? null,
                'is_indemnity'     => optional($customer->manifest->first())->is_indemnity ?? null,
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


                $paxList->push([
                    'reference' => $this->booking->reference ?? null,
                    'booking_item_id'  => $bookingItemId,
                    'type'      => $type,
                    'customer_id' => null,
                    'full_name'      => null,
                    'email_address'     => null,
                    'contact_number'     => null,
                    'age'       => null,
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
}
