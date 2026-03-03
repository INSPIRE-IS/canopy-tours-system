<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Mail\BookingMail;
use App\Models\Alert;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInfo;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingNote;
use App\Models\BookingPayment;
use App\Models\Closeout;
use App\Models\Email;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Inventory;
use App\Models\InventoryOverride;
use App\Models\InventoryTimeSlot;
use App\Models\Promotion;
use App\Models\QuestionAnswer;
use App\Models\Season;
use App\Models\Service;
use App\Models\TypeAffiliate;
use App\Models\TypeLocation;
use App\Models\TypeReferral;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Wholesaler;
use App\Models\WholesalerBooking;
use App\Models\WholesalerRate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Str;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\InvoiceMail;
use App\Mail\MessageGuestMail;
use App\Models\BoardingPassTemplate;
use App\Models\BookingDiscount;
use App\Models\BookingPricing;
use App\Models\BookingVoucher;
use App\Models\Continent;
use App\Models\File;
use App\Models\GiftVoucher;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\TypeInvoice;
use App\Models\Questionnaire;
use App\Models\State;
use App\Models\Team;
use App\Models\TypeAlert;
use App\Models\TypeBooking;
use App\Models\TypeConfirmation;
use App\Models\TypeGroup;
use App\Models\LookupItem;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookingsController extends Controller
{
    private function validateSlotCapacity($service, string $bookingDate, int $inventoryTimeSlotId, int $requestedPax, int $teamId, ?int $excludeBookingItemId = null): void
    {
        if (!$service || $service->capacity === null) {
            return;
        }

        $bookedQuery = BookingInventory::whereDate('date', $bookingDate)
            ->where('inventory_time_slot_id', $inventoryTimeSlotId)
            ->whereHas('booking_item', function ($query) use ($service, $teamId, $excludeBookingItemId) {
                $query->where('service_id', $service->id)
                    ->where('team_id', $teamId);
                if ($excludeBookingItemId) {
                    $query->where('id', '!=', $excludeBookingItemId);
                }
            });

        $booked = (int) $bookedQuery->sum('pax');
        $remaining = (int) $service->capacity - $booked;

        if ($remaining < $requestedPax) {
            throw ValidationException::withMessages([
                'start_time' => "Selected time slot does not have enough capacity. Available capacity: {$remaining}.",
            ]);
        }
    }

    private function logAudit(string $description, Booking $booking, ?BookingItem $bookingItem = null, array $properties = []): void
    {
        $payload = [
            'description' => $description,
            'user_id' => auth()->id(),
            'properties' => json_encode($properties),
            'host' => request()->ip(),
        ];

        AuditLog::create(array_merge($payload, [
            'subject_type' => Booking::class,
            'subject_id' => $booking->id,
        ]));

        if ($bookingItem) {
            AuditLog::create(array_merge($payload, [
                'subject_type' => BookingItem::class,
                'subject_id' => $bookingItem->id,
            ]));
        }
    }
    public function index()
    {

        abort_if(Gate::denies('bookings_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //$bookings = Booking::all();
        // dd(auth()->user()->team_id);

        //$bookings->load("booking_payments");
        //echo $bookings;
        $bookings = Booking::with([
            'booking_payments',
        ])
            ->where('team_id', auth()->user()->team_id)
            ->get();

        $booking_items = BookingItem::with([
            'booking.files',
            'customers',
            'customer_manifests',
            'booking_detail',
            'booking_fee',
            'booking_inventory',
            'booking_inventory.inventory_time_slot',
            'service',
            'season',
        ])
            ->where('team_id', auth()->user()->team_id)
            ->get();
        //dd($booking_items);
        $services = Service::with('seasons', 'seasons.inventory.inventory_time_slots')->get();

        $vatLookup = LookupItem::findDefaultForTeam('vat_rate', auth()->user()->team_id);
        $vatMultiplier = $vatLookup?->multiplier ?? 1.15;

        $formatted = $booking_items->map(function ($booking_item) use ($vatMultiplier) {
            try {
                $price = $booking_item?->booking_fee->tot_amount;
                $paid = $booking_item?->booking_fee->tot_paid;
                $due = $price - $paid;
                $service = $booking_item->service ?? Service::find($booking_item->service_id);
                $timeSlot = $booking_item->booking_inventory?->inventory_time_slot
                    ?? InventoryTimeSlot::find($booking_item->booking_inventory?->inventory_time_slot_id);

                $start_time = $timeSlot?->start_time ? Carbon::parse($timeSlot->start_time) : null;

                if ($start_time && $service?->duration) {
                    list($hours, $minutes, $seconds) = explode(':', $service->duration);
                    $end_time = $start_time->copy()->addHours($hours)->addMinutes($minutes)->addSeconds($seconds);
                } else {
                    $end_time = null;
                }

                $leadCustomer = $booking_item->customers->firstWhere('pivot.is_lead', 1);
                if (!$leadCustomer && $booking_item->booking?->customer_id) {
                    $leadCustomer = Customer::find($booking_item->booking->customer_id);
                }
                $leadName = $leadCustomer?->full_name ?? $booking_item->booking->lead_name ?? 'Test Customer';
                $leadEmail = $leadCustomer?->email_address ?? '';
                $leadPhone = $leadCustomer?->contact_number ?: 'Test Phone';
                $leadRow = [
                    'customer_id' => $leadCustomer?->id,
                    'full_name' => $leadName,
                    'email_address' => $leadEmail,
                    'contact_number' => $leadPhone,
                    'type' => 'adult',
                    'is_lead' => true,
                    'is_indemnity' => false,
                    'files' => $booking_item->booking?->files,
                ];

                $customersList = [$leadRow];
                $indemnityManifest = $booking_item->customer_manifests
                    ?->firstWhere('is_indemnity', 1);
                if ($indemnityManifest?->customer_id) {
                    $indemnityCustomer = $booking_item->customers
                        ->firstWhere('id', $indemnityManifest->customer_id);
                    if ($indemnityCustomer) {
                        if ($leadCustomer?->id && $leadCustomer->id === $indemnityCustomer->id) {
                            $customersList[0]['is_indemnity'] = true;
                        } else {
                            $customersList[] = [
                                'customer_id' => $indemnityCustomer->id,
                                'full_name' => $indemnityCustomer->full_name,
                                'email_address' => $indemnityCustomer->email_address,
                                'contact_number' => $indemnityCustomer->contact_number,
                                'type' => 'adult',
                                'is_lead' => false,
                                'is_indemnity' => true,
                                'files' => $booking_item->booking?->files,
                            ];
                        }
                    }
                }

                $bookingNotes = BookingNote::with('creator:id,name')
                    ->where('booking_id', $booking_item->booking_id)
                    ->where('booking_item_id', $booking_item->id)
                    ->where('type', 'booking_note')
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(function ($note) {
                        return [
                            'id' => $note->id,
                            'note' => $note->note,
                            'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
                            'created_by' => $note->creator?->name,
                        ];
                    });
                $latestBookingNote = $bookingNotes->first()['note'] ?? null;

                $info = [
                    'id' => $booking_item->booking->id,
                    'booking_item_id' => $booking_item->id,
                    'reference' => $booking_item->booking->reference,
                    'lead_name' => $leadName,
                    'lead_email' => $leadEmail,
                    'lead_phone' => $leadPhone,
                    'lead_id' => $leadCustomer?->id ?? $booking_item->booking->customer_id,
                    'bookingPax' => $booking_item->booking_inventory->pax, // $booking->customers->count(),
                    'maxPax' => $service->capacity, // $booking->customers->count(),
                    'service' => $booking_item->service->name,
                    'time_start' => $start_time?->format('H:i:s'),
                    'time_end' => $end_time?->format('H:i:s'),
                    'price' => round($price ?? 0, 2),
                    'paid' =>  round($paid ?? 0, 2),
                    'due' =>  round($due ?? 0, 2),
                    'customers' => $customersList,
                    'dueStatus' => $due > 0 ? true : false,
                    'due_date' => $booking_item->booking->due_date,
                    'booking_date' => $booking_item->booking_inventory->date,
                    'inventory_time_slot_id' => $booking_item->booking_inventory->inventory_time_slot_id,
                    'service_id' => $booking_item->service_id,
                    'season_id' => $booking_item->season_id,
                    'booking_note' => $latestBookingNote,
                    'booking_notes' => $bookingNotes,
                ];
                return $info;
            } catch (\Throwable $e) {
                Log::error('Booking', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'input' => $booking_item,
                ]);
                return null;
            }
        });

        $lookups = LookupItem::defaultsForTeam(auth()->user()->team_id);
        $today = now()->toDateString();
        $activities = Service::whereHas('seasons', function ($query) use ($today) {
            $query->whereDate('end_date', '>=', $today);
        })
            ->with(['seasons' => function ($query) use ($today) {
                $query->whereDate('end_date', '>=', $today);
            }])
            ->get();
        //dd($formatted);

        return Inertia::render('Admin/Bookings/Index', [
            'bookings' => $formatted,
            'lookups' => $lookups,
            'services' => $services,
            'activities' => $activities,
            // 'promotions' => $promotions
        ]);
    }

    public function create(Request $request)
    {

        $users = User::select('name', 'id')->get();
        $today = now()->toDateString();
        $status_bookings = TypeBooking::select('name', 'id')->get();
        $wholesalers = Wholesaler::select('id', 'name')->get();
        //$services = Service::select('id', 'name')->get();
        $services = Service::select('id', 'name', 'allow_internal_capacity_override', 'duration', 'capacity')
            ->whereHas('seasons', function ($query) use ($today) {
                $query->whereDate('end_date', '>=', $today);
            })
            ->with([
                'seasons' => function ($query) use ($today) {
                    $query->select('id', 'name', 'start_date', 'end_date', 'service_id', 'rake_rate_adult', 'rake_rate_child', 'rake_rate_guide', 'allow_internal_price_override', 'status')
                        ->whereDate('end_date', '>=', $today);
                },
                'seasons.inventory',
                'seasons.inventory.inventory_time_slots',
            ])
            ->get();
        //$seasons = Season::select('id', 'name', 'service_id')->get();
        $seasons = Season::select('start_date', 'id')->get();
        // $inventories = Inventory::select('start_date', 'id')->get();
        $type_affiliates = TypeReferral::select('name', 'id')->get();
        $updated_bies = User::select('name', 'id')->get();
        $customers = Customer::select('full_name as name', 'id')->get();

        $viewMode = $request->input('view_mode', 'week');
        $startDate = Carbon::parse($request->input('start_date', now()));
        $timeslots = Booking::TIME_SLOTS;

        $schedule = [];
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7
        };
        $endDate = (clone $startDate)->addDays($days - 1);

        $vouchers = Voucher::select('id', 'voucher_code as name', 'amount', 'note', 'expire_date', 'season_id', 'service_id')->with('season', 'service')->get();
        $discounts = Discount::select('id', 'name', 'applies_to', 'percent_off')->with('services')->get();
        //dd($discounts);
        $wholesaler_rates = WholesalerRate::select('id', 'wholesaler_id', 'service_id', 'season_id', 'price_adult', 'price_child', 'price_guide')
            ->with('service', 'season', 'wholesaler')
            ->get();
        $promos = Promotion::select('id', 'name', 'promo_code', 'type', 'value', 'service_id', 'booking_start_date', 'booking_end_date', 'activity_start_date', 'activity_end_date')
            ->with('service')
            ->get();
        $lookups = LookupItem::defaultsForTeam(auth()->user()->team_id);
        $settings = LookupItem::settingsForTeam(auth()->user()->team_id);
        $countries = Country::select('id', 'name', 'phonecode')
            ->whereNotNull('phonecode')
            ->orderBy('name')
            ->get();
        $this->logPromoDiscountVisibility(
            $request->input('service_id') ? (int) $request->input('service_id') : null,
            'booking_create'
        );
        return Inertia::render('Admin/Bookings/Create', [
            'wholesalers' => $wholesalers,
            'services' => $services,
            'inventories' => $seasons,
            'type_affiliates' => $type_affiliates,
            'updated_bies' => $updated_bies,
            'users' => $users,
            'status_bookings' => $status_bookings,
            'schedule' => $schedule,
            'start_date' => $startDate->toDateString(),
            'view_mode' => $viewMode,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'adults' => 0,
            'children' => 0,
            'guides' => 0,
            'availableTimes' => [],
            'vouchers' => $vouchers,
            'discounts' => $discounts,
            'wholesaler_rates' => $wholesaler_rates,
            'promos' => $promos,
            'lookups' => $lookups,
            'settings' => $settings,
            'countries' => $countries,
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        //dd($request->all());

        $validated = $request->validate([
            'booking_id' => 'integer', //   "lead_person" => "Lead Person"
            'lead_person' => 'required|string', //   "lead_person" => "Lead Person"
            'lead_email' => 'required|email', //   "lead_email" => "Lead Email"
            'lead_mobile' => 'required|string', //   "lead_mobile" => "Lead Mobile"
            'selected_slot' => 'required|array', //   "selected_slot" => array:3 [▶] date "2025-07-28" "selected_slot" => array:3 [▶] time "08:00:00"

            'service_id' => 'required|integer', //   "season_id" => "2"
            'season_id' => 'required|integer', //   "season_id" => "2"
            'corporate_customer' => 'boolean', //   "corporate_customer" => "true"
            'send_sms' => 'boolean', //   "send_sms" => "true"
            'booking_date' => 'required|date', //   "booking_date" => "2025-07-28"
            'start_time' => 'required', //   "start_time" => "08:00:00"

            'add_consultant' => 'boolean', //   "add_consultant" => "true"
            'consultant_name' => 'string|nullable', //   "consultant_name" => "Consultant Name"
            'consultant_email' => 'email|nullable', //   "consultant_email" => "Consultant Email"
            'consultant_mobile' => 'string|nullable', //   "consultant_mobile" => "Consultant Email Consultant Mobile"
            'needs_pickup' => 'boolean', //   "needs_pickup" => "true"
            'web_booking' => 'boolean', //   "web_booking" => "true"
            'include_media' => 'boolean', //   "videos_photos" => "true"
            'override_timeslots' => 'boolean', //   "override_timeslots" => "true"
            'override_rates' => 'boolean',
            'exclusive_resource' => 'boolean', //   "exclusive_resource" => "true"

            'invoice_note' => 'string|nullable', //   "invoice_note" => "Add Invoice Note"
            'email_reminder' => 'string|nullable', //   "email_reminder" => "Add Email Reminder"
            'booking_note' => 'string|nullable', //   "booking_note" => "Add Booking Note"

            'adults' => 'required|integer|min:0', //   "adults" => "1"
            'children' => 'nullable|integer|min:0', //   "children" => "2"      
            'guides' => 'nullable|integer|min:0', //   "guides" => "2"
            'adult' => 'integer|nullable',
            'child' => 'integer|nullable',
            'guide' => 'integer|nullable',
            'rate_per_adult' => 'integer|nullable',
            'rate_per_child' => 'integer|nullable',
            'rate_per_guide' => 'integer|nullable',
            'override_reason' => 'required_if:override_rates,1|nullable|string',

            'is_wholesaler' => 'boolean', //   "is_wholesaler" => "true"
            'wholesaler_id' => 'integer|nullable', //   "wholesaler_id" => "1"
            'discount_lines' => 'array|nullable',
            'promoCode' => 'nullable|string',
            'redeem_voucher' => 'string|nullable', //   "redeem_voucher" => "Voucher Code"
            'pay_later' => 'boolean', //   "pay_later" => "true"
            'send_invoice' => 'boolean',
            'send_quote' => 'boolean',
        ]);
        $settings = LookupItem::settingsForTeam(auth()->user()->team_id);
        $pickupEnabled = $settings['enable_pickup_dropoff'] ?? true;
        $autoSendInvoiceSetting = $settings['auto_send_invoice_on_booking'] ?? true;
        $autoSendQuoteSetting = $settings['auto_send_quote_on_booking'] ?? false;
        $autoSendInvoice = $autoSendInvoiceSetting ? true : (bool) ($validated['send_invoice'] ?? false);
        $autoSendQuote = $autoSendQuoteSetting ? true : (bool) ($validated['send_quote'] ?? false);
        $validated['needs_pickup'] = $pickupEnabled ? ($validated['needs_pickup'] ?? false) : false;
        try {
            $booking = DB::transaction(function () use ($validated, $autoSendInvoice, $autoSendQuote) {
                $renderService = app(RendererService::class);
                $season_id = $validated['season_id'];
                $selected_slot = $validated['selected_slot'];
                //dd($selected_slot['date']);
                $season = Season::findOrFail($season_id);
                $service = Service::findOrFail($season->service_id);
                $overrideRates = !empty($validated['override_rates']);
                if ($overrideRates && !(bool) $season->allow_internal_price_override) {
                    throw ValidationException::withMessages([
                        'override_rates' => 'Override rates are not allowed for this season.',
                    ]);
                }
                if ($overrideRates) {
                    $validated['promoCode'] = null;
                    $validated['discount_lines'] = [];
                    $validated['wholesaler_id'] = null;
                    $validated['is_wholesaler'] = false;
                }
                $inventory = Inventory::where('season_id', $season->id)->first();
                //$FullNameOfDay = Carbon::parse($validated['date'])->format('l'); // e.g., Monday
                //$ShortNameOfDay = Carbon::parse($validated['date'])->format('D'); // e.g., Mon
                $isoNameOfDay = Carbon::parse($selected_slot['activityDate'])->format('N'); // iso week starts on Monday
                //$numericNameOfDay = Carbon::parse($validated['date'])->format('w'); // week starts on Sunday

                // if (($validated['wholesaler_id'] && $validated['promoCode']) || (!$validated['wholesaler_id'] && $validated['discount_lines']) || ($validated['promoCode'] && $validated['discount_lines'])) {
                //     abort(404, 'Tonly one type of discount is permitted.');
                // }

                if (!$inventory) {
                    abort(404, 'This season has no inventory.');
                }
                $inventoryTimeSlot = InventoryTimeSlot::where('inventory_id', $inventory->id)->where('start_time', $selected_slot['time'])->first();
                //$inventoryTypeDay = InventoryTypeDay::where('inventory_id', $inventory->id)->first();

                if (!$inventoryTimeSlot && !$validated['override_timeslots']) {
                    abort(404, 'This inventory has no time slot.');
                }
                $adultsCount = (int) ($validated['adults'] ?? 0);
                $amount_adults = (int) ($validated['rate_per_adult'] ?? 0) * $adultsCount;
                $childrenCount = (int) ($validated['children'] ?? 0);
                $amount_children = (int) ($validated['rate_per_child'] ?? 0) * $childrenCount;
                $guidesCount = (int) ($validated['guides'] ?? 0);
                $amount_guides = (int) ($validated['rate_per_guide'] ?? 0) * $guidesCount;
                $requestedPax = $adultsCount + $childrenCount;
                $canOverrideCapacity = !empty($validated['override_timeslots']) && (bool) $service->allow_internal_capacity_override;
                if (!$canOverrideCapacity && $inventoryTimeSlot) {
                    $this->validateSlotCapacity(
                        $service,
                        $validated['booking_date'],
                        $inventoryTimeSlot->id,
                        $requestedPax,
                        auth()->user()->team_id
                    );
                }
                // TODO figure out why we changed to only amount below that seems like a bad idea
                $amount = $amount_adults + $amount_children + $amount_guides;
                // $amount = (int) ($validated['adult'] ?? 0) + (int) ($validated['child'] ?? 0) + (int) ($validated['guide'] ?? 0);
                // TODO: if we ever move to multiple bookings per customer, we need to add the booking fee to the amount
                $booking = null; // Booking::where('id', $validated['booking_id'])->where('status_payment_id', 1)->first();
                if (!$booking) {
                    // 1. Create or find customer
                    $customer = Customer::firstOrCreate([
                        'email_address' => $validated['lead_email'],
                    ], [
                        'full_name' => $validated['lead_person'],
                        'contact_number' => $validated['lead_mobile'],
                    ]);
                    $vatLookup = LookupItem::findDefaultForTeam('vat_rate', auth()->user()->team_id);
                    $vatMultiplier = $vatLookup?->multiplier ?? 1.15;
                    // 2. Create booking
                    $booking = Booking::create([
                        'user_id' => auth()->user()->id,
                        'session_id' => null,
                        'lead_name' => $customer->full_name,
                        'type_booking_id' => 1,
                        'status_payment_id' => 1,
                        'is_sms' => $validated['send_sms'] ?? 0,
                        'is_corporate' => $validated['corporate_customer'],
                        'due_date' => $validated['booking_date'],
                        'tot_paid' => 0,
                        'grand_tot_due' => $amount,
                        'reference' => 'REF-' . strtoupper(Str::random(6)), //Str::uuid(),
                        'customer_id' => $customer->id,
                        'team_id' => auth()->user()->team_id,
                    ]);
                } else {
                    $booking->update([
                        'grand_tot_due' => $booking->grand_tot_due + $amount,
                        'updated_at' => now(),
                    ]);
                }

                $booking_price  = null;
                if (is_array($validated['discount_lines']) && $validated['discount_lines'] != null) {
                    $booking_discount = BookingDiscount::create([
                        'booking_id'     => $booking->id,
                        'final_price_adult'    => (int) ($validated['adult'] ?? 0),
                        'final_price_child'    => (int) ($validated['child'] ?? 0),
                        'final_price_guide'    => (int) ($validated['guide'] ?? 0),
                    ]);
                    // Remove old discount lines if you want "sync" behavior
                    $booking_discount->booking_discount_items()->delete();
                    foreach ($validated['discount_lines'] as $line) {
                        $booking_discount->booking_discount_items()->updateOrCreate(
                            [
                                'pax_type'    => $line['type'],
                                'discount_id' => $line['discount_id'],
                            ],
                            [
                                'count'       => $line['count'],
                                'base_rate'   => $line['base_rate'],
                                'final_rate'  => $line['final_rate'],
                            ]
                        );
                    }
                }

                $promotion = Promotion::where('promo_code', $validated['promoCode'])->first();

                $booking_item = BookingItem::create([
                    'booking_id'     => $booking->id,
                    'service_id'     => $season->service_id,
                    'season_id'      => $season->id,
                    'inventory_id'   => $inventory->id,
                    'pax_adults'     => (int) ($validated['adults'] ?? 0),
                    'pax_children'   => (int) ($validated['children'] ?? 0),
                    'pax_guides'     => (int) ($validated['guides'] ?? 0),
                    'price_adult'    => (int) ($validated['rate_per_adult'] ?? 0),
                    'price_child'    => (int) ($validated['rate_per_child'] ?? 0),
                    'price_guide'    => (int) ($validated['rate_per_guide'] ?? 0),
                    'promotion_id'   => $promotion ? $promotion->id : null,
                    'wholesaler_id'  => $validated['wholesaler_id'],
                    'discount_id'    => $booking_price ? $booking_price->id : null,
                    'team_id' => auth()->user()->team_id,
                ]);

                // Prepare pivot data
                $pivotData = [];

                $pivotData[$customer->id] = [
                    'team_id' => auth()->user()->team_id,   // TODO: get team from the page or session
                    'is_lead' => 1,     // First customer is lead, rest are not
                ];

                // Sync with pivot data
                $booking_item->customers()->sync($pivotData);

                $bookingDetail = BookingDetail::create([
                    'booking_item_id'     => $booking_item->id,
                    'is_wholesaler_booking' => $validated['is_wholesaler'],
                    'is_needs_pickup' => $validated['needs_pickup'],
                    'is_web_booking' => $validated['web_booking'],
                    'is_videos_photos' => $validated['include_media'],
                    'is_exclusive_resource_use' => $validated['exclusive_resource'],
                    'is_override_time_slots' => $validated['override_timeslots'],
                    'team_id'        => auth()->user()->team_id,
                ]);

                if ($validated['is_wholesaler']) {
                    WholesalerBooking::create([
                        'booking_id'     => $booking->id,
                        'wholesaler_id'     => $validated['wholesaler_id'],
                        'consultant_name' => $validated['consultant_name'] ?? '',
                        'consultant_email' => $validated['consultant_email'] ?? '',
                        'consultant_mobile' => $validated['consultant_mobile'] ?? '',
                        'team_id'        => auth()->user()->team_id,
                    ]);
                }
                $inventoryOverride = null;
                if ($overrideRates) {
                    $overrideTime = $inventoryTimeSlot?->start_time ?? ($selected_slot['time'] ?? '00:00:00');
                    $inventoryOverride = InventoryOverride::create([
                        'service_id' => $season->service_id,
                        'season_id' => $season->id,
                        'inventory_id' => $inventory->id,
                        'date' => $validated['booking_date'],
                        'time' => $overrideTime,
                        'capacity_override' => (int) ($service->capacity ?? 0),
                        'price_adult' => (float) ($validated['rate_per_adult'] ?? 0),
                        'price_child' => (float) ($validated['rate_per_child'] ?? 0),
                        'price_guide' => (float) ($validated['rate_per_guide'] ?? 0),
                        'note' => $validated['override_reason'] ?? '',
                        'created_by_id' => auth()->id(),
                        'updated_by_id' => auth()->id(),
                        'team_id' => auth()->user()->team_id,
                    ]);
                }

                // TODO add modal to capture and update inventory overrides then remove the commented code below
                $bookingInventory = BookingInventory::create([
                    'booking_item_id'     => $booking_item->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'adults'     => (int) ($validated['adults'] ?? 0),
                    'children'   => (int) ($validated['children'] ?? 0),
                    'guides'     => (int) ($validated['guides'] ?? 0),
                    'pax'            => (int) ($validated['adults'] ?? 0) + (int) ($validated['children'] ?? 0),
                    'date'           => $validated['booking_date'],
                    'inventory_type_day_id' => $isoNameOfDay,
                    'inventory_time_slot_id' => $inventoryTimeSlot->id,
                    'inventory_override_id' => $overrideRates ? $inventoryOverride?->id : null,
                    'team_id'        => auth()->user()->team_id,
                ]);
                // TODO add logic for creating and validating a voucher code
                $bookingFee = BookingFee::create([
                    'booking_item_id'     => $booking_item->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'tot_adults'     => $amount_adults,
                    'tot_children'   => $amount_children,
                    'tot_guides'     => $amount_guides,
                    'is_voucher'     => $validated['redeem_voucher'] ? 1 : 0,
                    'voucher_code'   => $validated['redeem_voucher'] ?? '',
                    'tot_amount'     => $amount,
                    'team_id'        => auth()->user()->team_id,
                ]);

                $this->createBookingNotes($booking, $booking_item, $validated);
                $this->logAudit('Booking created', $booking, $booking_item, [
                    'service_id' => $booking_item->service_id,
                    'season_id' => $booking_item->season_id,
                    'amount' => $amount,
                    'override_rates' => $overrideRates,
                    'override_reason' => $validated['override_reason'] ?? null,
                ]);

                $invoiceTypeId = $this->getTypeInvoiceId(TypeInvoice::SLUG_STD);
                // TODO: re-enable template-driven invoices when clients are ready.
                $template = InvoiceTemplate::where('is_default', true)
                    ->where('type_invoice_id', $invoiceTypeId)
                    ->where('team_id', auth()->user()->team_id)
                    ->first();
                $amountExVat = round(($amount / 1.15), 2);
                $vatAmount = round(($amount - $amountExVat), 2);
                $invoice = Invoice::create([
                    'booking_id'     => $booking->id,
                    'gift_voucher_id'    => null,
                    'invoice_date'   => $validated['booking_date'],
                    'due_date'       => $validated['booking_date'],
                    'subtotal'       => $amountExVat,
                    'vat'            => $vatAmount,
                    'total'          => $amount,
                    'status'         => 'draft',
                    'invoice_template_id'    => $template?->id,
                    'type_invoice_id' => $invoiceTypeId,
                    'team_id'        => auth()->user()->team_id,

                ]);

                $invoiceItems = InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'pax_adults'     => (int) ($validated['adults'] ?? 0),
                    'pax_children'   => (int) ($validated['children'] ?? 0),
                    'pax_guides'     => (int) ($validated['guides'] ?? 0),
                    'price_adult'    => (int) ($validated['adult'] ?? 0),
                    'price_child'    => (int) ($validated['child'] ?? 0),
                    'price_guide'    => (int) ($validated['guide'] ?? 0),
                    'team_id'        => auth()->user()->team_id,
                    'service_id'     => $season->service_id,
                    'season_id'      => $season->id,
                    'booking_inventory_id' => $bookingInventory->id,
                    'promotion_id'   => $promotion ? $promotion->id : null,
                    'wholesaler_id'  => $validated['wholesaler_id'],
                    'discount_id'    => $booking_price ? $booking_price->id : null,
                ]);

                $site = Team::select('full_name', 'reg_no', 'address', 'address_code', 'account_owner', 'account_number', 'account_type', 'bank_name', 'branch_code')
                    ->find(auth()->user()->team_id);
                $items = $booking?->booking_items->first();
                $data = $renderService->invoice($booking, $customer, $invoice);
                $invoicePdf = Pdf::loadView('invoices.invoice', $data);
                // Optional: tweak DOMPDF options
                $invoicePdf->setPaper('A4', 'portrait');


                $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
                Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());
                $documentLabel = $data['document']['label'] ?? 'Invoice';
                $documentLabelLower = strtolower($documentLabel);
                $reminderContent = !empty($validated['email_reminder'])
                    ? "<p><strong>Reminder</strong></p><p>{$validated['email_reminder']}</p>"
                    : '';
                $content = "
                        <p>Hello {$customer->full_name},</p>
                        <p>Your booking has been created. Payment is required to confirm.</p>
                        <p>Thank you for choosing Canopy Tours.</p>
                        <p>Your booking reference is {$booking->reference}.</p>
                        {$reminderContent}
                        <p>Please contact us if you have any questions.</p>";
                // send mail
                // 🔹 log in emails table
                if ($autoSendInvoice) {
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $validated['lead_email'],
                        'subject'        => $documentLabel . ' ' . $data['invoice']['number'],
                        'body'           => strip_tags($content), // or a summary
                        'attachment_path' => $invoiceFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => auth()->user()->id,
                        'auditable_type' => 'App\Models\Booking',
                        'auditable_id'   => $booking->id,
                        'team_id'        => auth()->user()->team_id,
                    ]);
                    $this->logAudit('Email sent (invoice)', $booking, $booking_item, [
                        'subject' => $documentLabel . ' ' . $data['invoice']['number'],
                        'receiver' => $validated['lead_email'],
                        'attachment' => $invoiceFilename,
                    ]);
                }
                File::create([
                    'team_id'      => $invoice->team_id,
                    'table_name'   => 'invoices',
                    'table_row_id' => $invoice->id,
                    'file_name'    => $data['invoice']['number'] . '.pdf',
                    'file_type'    => "application/pdf",
                    'type_file_id' => 4, // invoice
                    'path'         => $invoiceFilename,
                    'booking_id'   => $booking->id,
                ]);
                if ($autoSendQuote) {
                    $quoteTypeId = $this->getTypeInvoiceId(TypeInvoice::SLUG_QUOTE);
                    // TODO: re-enable template-driven quotes when clients are ready.
                    $quoteTemplate = InvoiceTemplate::where('is_default', true)
                        ->where('type_invoice_id', $quoteTypeId)
                        ->where('team_id', auth()->user()->team_id)
                        ->first();
                    $quoteInvoice = Invoice::create([
                        'booking_id'     => $booking->id,
                        'gift_voucher_id'    => null,
                        'invoice_date'   => $validated['booking_date'],
                        'due_date'       => $validated['booking_date'],
                        'subtotal'       => $amountExVat,
                        'vat'            => $vatAmount,
                        'total'          => $amount,
                        'status'         => 'draft',
                        'invoice_template_id'    => $quoteTemplate?->id,
                        'type_invoice_id' => $quoteTypeId,
                        'team_id'        => auth()->user()->team_id,
                    ]);

                    InvoiceItem::create([
                        'invoice_id'     => $quoteInvoice->id,
                        'pax_adults'     => (int) ($validated['adults'] ?? 0),
                        'pax_children'   => (int) ($validated['children'] ?? 0),
                        'pax_guides'     => (int) ($validated['guides'] ?? 0),
                        'price_adult'    => (int) ($validated['adult'] ?? 0),
                        'price_child'    => (int) ($validated['child'] ?? 0),
                        'price_guide'    => (int) ($validated['guide'] ?? 0),
                        'team_id'        => auth()->user()->team_id,
                        'service_id'     => $season->service_id,
                        'season_id'      => $season->id,
                        'booking_inventory_id' => $bookingInventory->id,
                        'promotion_id'   => $promotion ? $promotion->id : null,
                        'wholesaler_id'  => $validated['wholesaler_id'],
                        'discount_id'    => $booking_price ? $booking_price->id : null,
                    ]);

                    $quoteData = $renderService->invoice($booking, $customer, $quoteInvoice);
                    $quotePdf = Pdf::loadView('invoices.invoice', $quoteData);
                    $quotePdf->setPaper('A4', 'portrait');
                    $quoteFilename = 'quotes/' . $quoteData['invoice']['number'] . '.pdf';
                    Storage::disk('public')->put($quoteFilename, $quotePdf->output());

                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $validated['lead_email'],
                        'subject'        => ($quoteData['document']['label'] ?? 'Quote') . ' ' . $quoteData['invoice']['number'],
                        'body'           => strip_tags($content),
                        'attachment_path' => $quoteFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => auth()->user()->id,
                        'auditable_type' => 'App\Models\Booking',
                        'auditable_id'   => $booking->id,
                        'team_id'        => auth()->user()->team_id,
                    ]);
                    $this->logAudit('Email sent (quote)', $booking, $booking_item, [
                        'subject' => ($quoteData['document']['label'] ?? 'Quote') . ' ' . $quoteData['invoice']['number'],
                        'receiver' => $validated['lead_email'],
                        'attachment' => $quoteFilename,
                    ]);

                    File::create([
                        'team_id'      => $quoteInvoice->team_id,
                        'table_name'   => 'invoices',
                        'table_row_id' => $quoteInvoice->id,
                        'file_name'    => $quoteData['invoice']['number'] . '.pdf',
                        'file_type'    => "application/pdf",
                        'type_file_id' => 4,
                        'path'         => $quoteFilename,
                        'booking_id'   => $booking->id,
                    ]);

                    Mail::to($customer->email_address)->send(new BookingMail($quoteData, [
                        [
                            'data' => $quotePdf->output(),
                            'name' => strtolower($quoteData['document']['label'] ?? 'quote') . "-{$quoteData['invoice']['number']}.pdf",
                            'mime' => 'application/pdf',
                        ],
                    ]));
                }
                /*
                 * Voucher email disabled: replace with boarding pass attachments for internal booking.
                 * We will revisit voucher fields when boarding pass data mapping is updated.
                 */

                $bookingItem = BookingItem::with(
                    'booking',
                    'booking.team',
                    'booking.team.file_logo',
                    'customers',
                    'booking_inventory.inventory_time_slot',
                    'booking_inventory.inventory_type_day.type_day',
                    'booking_fee'
                )->where('booking_id', $booking->id)->first();

                $passData = $this->getPassDetails($bookingItem);
                $html = view('pdfs.boarding-pass', ['data' => $passData])->render();
                $dir = "pass/{$bookingItem->id}";
                $fileName = "{$dir}/boarding_pass_{$bookingItem->id}.pdf";
                $disk = Storage::disk('public');
                $fullPath = $disk->path($fileName);
                if (!$disk->exists($fileName)) {
                    $disk->makeDirectory($dir);
                    $chromePath = config('browsershot.chrome_path');
                    if (empty($chromePath)) {
                        Log::warning('Browsershot chrome path not resolved; using default detection.', [
                            'env_path' => env('BROWSERSHOT_CHROME_PATH'),
                        ]);
                    }
                    $shot = Browsershot::html($html);
                    if (!empty($chromePath)) {
                        $shot->setChromePath($chromePath);
                    }
                    $shot->format('A4')
                        ->showBackground()
                        ->margins(0, 0, 0, 0)
                        ->scale(1.0)
                        ->timeout(60000)
                        ->save($fullPath);
                }

                if ($autoSendInvoice) {
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Your Boarding Pass – ' . $documentLabel . ' ' . $data['invoice']['number'],
                        'body'           => strip_tags($content),
                        'attachment_path' => $fileName,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => auth()->user()->id,
                        'auditable_type' => BookingItem::class,
                        'auditable_id'   => $bookingItem->id,
                        'team_id'        => $bookingItem->team_id,
                    ]);
                    $this->logAudit('Email sent (boarding pass)', $booking, $bookingItem, [
                        'receiver' => $customer->email_address,
                        'attachment' => $fileName,
                    ]);
                    $this->logAudit('Email sent (boarding pass)', $booking, $booking_item, [
                        'receiver' => $customer->email_address,
                        'attachment' => $fileName,
                    ]);
                }
                File::create([
                    'team_id'      => $bookingItem->team_id,
                    'table_name'   => 'BookingItem',
                    'table_row_id' => $bookingItem->id,
                    'file_name'    =>  $fileName,
                    'file_type'    => "application/pdf",
                    'type_file_id' => 6,
                    'path'         => $fileName,
                    'booking_id'   => $booking->id,
                ]);
                $attachments = [
                    [
                        'data' => $invoicePdf->output(),
                        'name' => "{$documentLabelLower}-{$data['invoice']['number']}.pdf",
                        'mime' => 'application/pdf',
                    ],
                    [
                        'data' => file_get_contents($fullPath),
                        'name' => $fileName,
                        'mime' => 'application/pdf',
                    ],
                ];
                // //send mail
                $messageData = [
                    'invoice' => [
                        'number' => $booking->reference ?? 'INV-No',
                        'date' => now()->format('Y-m-d'),
                    ],
                    'customer' => [
                        'name' => $customer->full_name,
                    ],
                    'booking' => [
                        'reference' => $booking->reference,
                    ],
                ];


                if ($autoSendInvoice) {
                    Mail::to($customer->email_address)->send(new BookingMail($messageData, $attachments));
                }


                // //send mail
                //$sent = Mail::to($validated['lead_email'])->send(new InvoiceMail($data, $pdf));

                // Log::info('invoice email sent', [
                //     'sent' => $sent,
                // ]);


                return $booking;
            });

            // 4. Save to booking_inventories or other linked tables
            // return response()->json([
            //     'success' => true,
            //     'booking_id' => $booking->id,
            //     'redirect_url' => route('front.payment.review', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
            // ]);
            //return redirect()->back()->with('booking_id', $booking->id);
            //return redirect()->route('admin.bookings.index');
            Log::info('booking created', [
                'booking' => $booking,
            ]);
            $message = "Booking {$booking->reference} saved successfully.";
            if ($validated['pay_later']) {
                return redirect()
                    ->route('admin.bookings.index')
                    ->with('success', $message);
            }

            return redirect()
                ->route('admin.booking-payments.create', $booking->id)
                ->with('success', $message);
            // return Inertia::location(route('admin.booking_payments.create') . '?booking_id=' . $booking->id);
            // return Inertia::location(route('admin.booking_payments.edit', $booking->id));
            // return redirect()->route('admin.booking_payments.edit', ['booking' => $booking->id]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Booking failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);
            return back()->with([
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ])->withInput();
            // return response()->json([
            //     'success' => false,
            //     'error' => 'Something went wrong while saving your booking. Please try again.',
            // ], 500);
        }


        // $booking = Booking::create($request->all());
        // $booking->customers()->sync($request->input('customers', []));

        //return redirect()->route('admin.bookings.index');
    }

    public function edit(Request $request, Booking $booking)
    {
        //abort_if(Gate::denies('booking_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $user = auth()->user();

        $booking->load([
            'booking_payments', // :id,booking_id,amount
            'booking_discounts.booking_discount_items.discount',
            'customer',
        ]);

        $booking_items = BookingItem::with([
            'service',
            'season',
            'inventory',
            'customers', // :id,name,email,contact_number
            'customer_manifests.customer.booking_questionnaire', // :id,position,notes,is_indemnity,customer_id,booking_id\
            'booking_infos', // :id,booking_id,type_location_id,location,time,created_at,updated_at,deleted_at
            'customers.booking_questionnaire', // :id,booking_id,customer_id,guide_id,safety_guide_id,type_group_id,fullname,email,signature,birth_date,type_referral,country_id,province_id,is_medical,medical_detail,is_fit,is_agree
            'booking_detail', //:id,booking_id ,start_time,end_time
            'booking_fee', //:id,booking_id,tot_amount
            'booking_inventory', // :id,booking_id,inventory_id
            'booking_inventory.inventory_override',
            'booking_staff', // :id,booking_id,user_id
            'booking_staff.user', // :id,name,email,contact_number
        ])->where('booking_id', $booking->id)->first();

        $users = User::select('name', 'id')->get();
        $today = now()->toDateString();
        $currentServiceId = $booking_items?->service_id;
        $currentSeasonId = $booking_items?->season_id;
        $status_bookings = TypeBooking::select('name', 'id')->get();
        $wholesalers = Wholesaler::select('id', 'name')->get();
        //$services = Service::select('id', 'name')->get();
        $services = Service::select('id', 'name', 'allow_internal_capacity_override', 'duration', 'capacity')
            ->where(function ($query) use ($today, $currentServiceId) {
                $query->whereHas('seasons', function ($subQuery) use ($today) {
                    $subQuery->whereDate('end_date', '>=', $today);
                });
                if ($currentServiceId) {
                    $query->orWhere('id', $currentServiceId);
                }
            })
            ->with([
                'seasons' => function ($query) use ($today, $currentSeasonId) {
                    $query->select('id', 'name', 'start_date', 'end_date', 'service_id', 'rake_rate_adult', 'rake_rate_child', 'rake_rate_guide', 'allow_internal_price_override', 'status')
                        ->where(function ($subQuery) use ($today, $currentSeasonId) {
                            $subQuery->whereDate('end_date', '>=', $today);
                            if ($currentSeasonId) {
                                $subQuery->orWhere('id', $currentSeasonId);
                            }
                        });
                },
                'seasons.inventory',
                'seasons.inventory.inventory_time_slots',
            ])
            ->get();
        //$seasons = Season::select('id', 'name', 'service_id')->get();
        $seasons = Season::select('start_date', 'id')->get();
        // $inventories = Inventory::select('start_date', 'id')->get();
        $type_affiliates = TypeReferral::select('name', 'id')->get();
        $updated_bies = User::select('name', 'id')->get();
        $customers = Customer::select('full_name as name', 'id')->get();

        $viewMode = $request->input('view_mode', 'week');
        $startDate = Carbon::parse($request->input('start_date', now()));
        $timeslots = Booking::TIME_SLOTS;

        $schedule = [];
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7
        };
        $endDate = (clone $startDate)->addDays($days - 1);

        $vouchers = Voucher::select('id', 'voucher_code as name', 'amount', 'note', 'expire_date', 'season_id', 'service_id')->with('season', 'service')->get();
        $discounts = Discount::select('id', 'name', 'applies_to', 'percent_off')->with('services')->get();
        $wholesaler_rates = WholesalerRate::select('id', 'wholesaler_id', 'season_id', 'price_adult', 'price_child', 'price_guide')->with('season', 'wholesaler')->get();
        $promos = Promotion::select('id', 'name', 'promo_code', 'type', 'value', 'service_id', 'booking_start_date', 'booking_end_date', 'activity_start_date', 'activity_end_date')
            ->with('service')
            ->get();
        $lookups = LookupItem::defaultsForTeam(auth()->user()->team_id);
        $settings = LookupItem::settingsForTeam(auth()->user()->team_id);
        $countries = Country::select('id', 'name', 'phonecode')
            ->whereNotNull('phonecode')
            ->orderBy('name')
            ->get();
        $this->logPromoDiscountVisibility(
            $currentServiceId ? (int) $currentServiceId : null,
            'booking_edit'
        );

        return Inertia::render('Admin/Bookings/Edit', [
            'wholesalers' => $wholesalers,
            'booking_items' => $booking_items,
            'booking' => $booking,
            'services' => $services,
            'inventories' => $seasons,
            'type_affiliates' => $type_affiliates,
            'updated_bies' => $updated_bies,
            'users' => $users,
            'status_bookings' => $status_bookings,
            'schedule' => $schedule,
            'start_date' => $startDate->toDateString(),
            'view_mode' => $viewMode,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'adults' => 0,
            'children' => 0,
            'guides' => 0,
            'availableTimes' => [],
            'vouchers' => $vouchers,
            'discounts' => $discounts,
            'wholesaler_rates' => $wholesaler_rates,
            'promos' => $promos,
            'lookups' => $lookups,
            'settings' => $settings,
            'countries' => $countries,
        ]);




        // $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $status_bookings = StatusBooking::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $inventories = Inventory::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');


        // $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $customers = Customer::pluck('full_name as name', 'id');

        // $booking->load('user', 'status_booking', 'service', 'season', 'inventory', 'updated_by', 'customers', 'team');

        // return view('admin.bookings.edit', compact('booking', 'customers', 'inventories', 'seasons', 'services', 'status_bookings', 'updated_bies', 'users'));
    }
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $validated = $request->validate([
            'booking_id' => 'nullable|integer',
            'lead_person' => 'required|string',
            'lead_email' => 'required|email',
            'lead_mobile' => 'required|string',
            'selected_slot' => 'required|array',

            'service_id' => 'required|integer',
            'season_id' => 'required|integer',
            'corporate_customer' => 'boolean',
            'send_sms' => 'boolean',
            'booking_date' => 'required|date',
            'start_time' => 'required',

            'add_consultant' => 'boolean',
            'consultant_name' => 'string|nullable',
            'consultant_email' => 'email|nullable',
            'consultant_mobile' => 'string|nullable',
            'needs_pickup' => 'boolean',
            'web_booking' => 'boolean',
            'include_media' => 'boolean',
            'override_timeslots' => 'boolean',
            'override_rates' => 'boolean',
            'exclusive_resource' => 'boolean',

            'invoice_note' => 'string|nullable',
            'email_reminder' => 'string|nullable',
            'booking_note' => 'string|nullable',

            'adults' => 'required|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'guides' => 'nullable|integer|min:0',
            'adult' => 'integer|nullable',
            'child' => 'integer|nullable',
            'guide' => 'integer|nullable',
            'rate_per_adult' => 'integer|nullable',
            'rate_per_child' => 'integer|nullable',
            'rate_per_guide' => 'integer|nullable',
            'override_reason' => 'required_if:override_rates,1|nullable|string',

            'is_wholesaler' => 'boolean',
            'wholesaler_id' => 'integer|nullable',
            'discount_lines' => 'array|nullable',
            'promoCode' => 'nullable|string',
            'redeem_voucher' => 'string|nullable',
            'pay_later' => 'boolean',
            'send_invoice' => 'boolean',
            'send_quote' => 'boolean',
        ]);
        $settings = LookupItem::settingsForTeam(auth()->user()->team_id);
        $pickupEnabled = $settings['enable_pickup_dropoff'] ?? true;
        $autoSendInvoiceSetting = $settings['auto_send_invoice_on_booking'] ?? true;
        $autoSendQuoteSetting = $settings['auto_send_quote_on_booking'] ?? false;
        $autoSendInvoice = $autoSendInvoiceSetting ? true : (bool) ($validated['send_invoice'] ?? false);
        $autoSendQuote = $autoSendQuoteSetting ? true : (bool) ($validated['send_quote'] ?? false);
        $validated['needs_pickup'] = $pickupEnabled ? ($validated['needs_pickup'] ?? false) : false;

        try {
            DB::transaction(function () use ($validated, $booking, $autoSendInvoice, $autoSendQuote) {
                $bookingItem = BookingItem::with(['booking_detail', 'booking_inventory', 'booking_fee', 'customers'])
                    ->where('booking_id', $booking->id)
                    ->firstOrFail();

                $selectedSlot = $validated['selected_slot'];
                $bookingDate = $validated['booking_date'];

                $season = Season::findOrFail($validated['season_id']);
                $service = Service::findOrFail($season->service_id);
                $overrideRates = !empty($validated['override_rates']);
                if ($overrideRates && !(bool) $season->allow_internal_price_override) {
                    throw ValidationException::withMessages([
                        'override_rates' => 'Override rates are not allowed for this season.',
                    ]);
                }
                if ($overrideRates) {
                    $validated['promoCode'] = null;
                    $validated['discount_lines'] = [];
                    $validated['wholesaler_id'] = null;
                    $validated['is_wholesaler'] = false;
                }
                $inventory = Inventory::where('season_id', $season->id)->first();
                if (!$inventory) {
                    abort(404, 'This season has no inventory.');
                }

                $inventoryTimeSlot = InventoryTimeSlot::where('inventory_id', $inventory->id)
                    ->where('start_time', $selectedSlot['time'])
                    ->first();

                if (!$inventoryTimeSlot && !$validated['override_timeslots']) {
                    abort(404, 'This inventory has no time slot.');
                }

                $isoNameOfDay = Carbon::parse($bookingDate)->format('N');

                $adultsCount = (int) ($validated['adults'] ?? 0);
                $childrenCount = (int) ($validated['children'] ?? 0);
                if (($adultsCount + $childrenCount) < 1) {
                    throw ValidationException::withMessages([
                        'adults' => 'At least one adult or child is required.',
                    ]);
                }
                if (($adultsCount + $childrenCount) < 1) {
                    throw ValidationException::withMessages([
                        'adults' => 'At least one adult or child is required.',
                    ]);
                }
                $guidesCount = (int) ($validated['guides'] ?? 0);
                $amount_adults = (int) ($validated['rate_per_adult'] ?? 0) * $adultsCount;
                $amount_children = (int) ($validated['rate_per_child'] ?? 0) * $childrenCount;
                $amount_guides = (int) ($validated['rate_per_guide'] ?? 0) * $guidesCount;
                $requestedPax = $adultsCount + $childrenCount;
                $canOverrideCapacity = !empty($validated['override_timeslots']) && (bool) $service->allow_internal_capacity_override;
                if (!$canOverrideCapacity && $inventoryTimeSlot) {
                    $this->validateSlotCapacity(
                        $service,
                        $bookingDate,
                        $inventoryTimeSlot->id,
                        $requestedPax,
                        auth()->user()->team_id,
                        $bookingItem->id
                    );
                }
                $amount = $amount_adults + $amount_children + $amount_guides;

                $inventoryOverride = null;
                if ($overrideRates) {
                    $overrideTime = $inventoryTimeSlot?->start_time ?? ($selectedSlot['time'] ?? '00:00:00');
                    $overridePayload = [
                        'service_id' => $season->service_id,
                        'season_id' => $season->id,
                        'inventory_id' => $inventory->id,
                        'date' => $bookingDate,
                        'time' => $overrideTime,
                        'capacity_override' => (int) ($service->capacity ?? 0),
                        'price_adult' => (float) ($validated['rate_per_adult'] ?? 0),
                        'price_child' => (float) ($validated['rate_per_child'] ?? 0),
                        'price_guide' => (float) ($validated['rate_per_guide'] ?? 0),
                        'note' => $validated['override_reason'] ?? '',
                        'updated_by_id' => auth()->id(),
                        'team_id' => auth()->user()->team_id,
                    ];
                    $existingOverrideId = $bookingItem->booking_inventory?->inventory_override_id;
                    if ($existingOverrideId) {
                        $inventoryOverride = InventoryOverride::find($existingOverrideId);
                        if ($inventoryOverride) {
                            $inventoryOverride->update($overridePayload);
                        }
                    }
                    if (!$inventoryOverride) {
                        $inventoryOverride = InventoryOverride::create(array_merge($overridePayload, [
                            'created_by_id' => auth()->id(),
                        ]));
                    }
                }

                $customer = Customer::firstOrCreate(
                    [
                        'email_address' => $validated['lead_email'],
                    ],
                    [
                        'full_name' => $validated['lead_person'],
                        'contact_number' => $validated['lead_mobile'],
                    ]
                );

                $customerUpdates = [];
                if (!empty($validated['lead_person']) && $customer->full_name !== $validated['lead_person']) {
                    $customerUpdates['full_name'] = $validated['lead_person'];
                }
                if (!empty($validated['lead_mobile']) && $customer->contact_number !== $validated['lead_mobile']) {
                    $customerUpdates['contact_number'] = $validated['lead_mobile'];
                }
                if (!empty($customerUpdates)) {
                    $customer->update($customerUpdates);
                }

                $booking->update([
                    'lead_name' => $customer->full_name,
                    'is_corporate' => $validated['corporate_customer'],
                    'is_sms' => $validated['send_sms'],
                    'due_date' => $bookingDate,
                    'grand_tot_due' => $amount,
                    'customer_id' => $customer->id,
                    'updated_by_id' => auth()->id(),
                ]);

                $promotion = !empty($validated['promoCode'])
                    ? Promotion::where('promo_code', $validated['promoCode'])->first()
                    : null;

                $bookingItem->update([
                    'service_id' => $season->service_id,
                    'season_id' => $season->id,
                    'inventory_id' => $inventory->id,
                    'pax_adults' => $adultsCount,
                    'pax_children' => $childrenCount,
                    'pax_guides' => $guidesCount,
                    'price_adult' => (int) ($validated['rate_per_adult'] ?? 0),
                    'price_child' => (int) ($validated['rate_per_child'] ?? 0),
                    'price_guide' => (int) ($validated['rate_per_guide'] ?? 0),
                    'promotion_id' => $promotion?->id,
                    'wholesaler_id' => $validated['wholesaler_id'],
                ]);

                DB::table('booking_items_customer')
                    ->where('booking_item_id', $bookingItem->id)
                    ->update(['is_lead' => 0]);
                $bookingItem->customers()->syncWithoutDetaching([
                    $customer->id => [
                        'team_id' => auth()->user()->team_id,
                        'is_lead' => 1,
                    ],
                ]);

                $bookingDetailData = [
                    'booking_item_id' => $bookingItem->id,
                    'is_wholesaler_booking' => $validated['is_wholesaler'],
                    'is_needs_pickup' => $validated['needs_pickup'],
                    'is_web_booking' => $validated['web_booking'],
                    'is_videos_photos' => $validated['include_media'],
                    'is_exclusive_resource_use' => $validated['exclusive_resource'],
                    'is_override_time_slots' => $validated['override_timeslots'],
                    'team_id' => auth()->user()->team_id,
                ];
                if ($bookingItem->booking_detail) {
                    $bookingItem->booking_detail->update($bookingDetailData);
                    $bookingDetail = $bookingItem->booking_detail;
                } else {
                    $bookingDetail = BookingDetail::create($bookingDetailData);
                }

                if ($validated['is_wholesaler']) {
                    WholesalerBooking::updateOrCreate(
                        [
                            'booking_id' => $booking->id,
                        ],
                        [
                            'wholesaler_id' => $validated['wholesaler_id'],
                            'consultant_name' => $validated['consultant_name'] ?? '',
                            'consultant_email' => $validated['consultant_email'] ?? '',
                            'consultant_mobile' => $validated['consultant_mobile'] ?? '',
                            'team_id' => auth()->user()->team_id,
                        ]
                    );
                } else {
                    WholesalerBooking::where('booking_id', $booking->id)->delete();
                }

                $bookingInventoryData = [
                    'booking_item_id' => $bookingItem->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'adults' => $adultsCount,
                    'children' => $childrenCount,
                    'guides' => $guidesCount,
                    'pax' => $adultsCount + $childrenCount,
                    'date' => $bookingDate,
                    'inventory_type_day_id' => $isoNameOfDay,
                    'inventory_time_slot_id' => $inventoryTimeSlot?->id,
                    'inventory_override_id' => $overrideRates ? $inventoryOverride?->id : null,
                    'team_id' => auth()->user()->team_id,
                ];
                if ($bookingItem->booking_inventory) {
                    $bookingItem->booking_inventory->update($bookingInventoryData);
                    $bookingInventory = $bookingItem->booking_inventory;
                } else {
                    $bookingInventory = BookingInventory::create($bookingInventoryData);
                }

                $bookingFeeData = [
                    'booking_item_id' => $bookingItem->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'tot_adults' => $amount_adults,
                    'tot_children' => $amount_children,
                    'tot_guides' => $amount_guides,
                    'is_voucher' => $validated['redeem_voucher'] ? 1 : 0,
                    'voucher_code' => $validated['redeem_voucher'] ?? '',
                    'tot_amount' => $amount,
                    'team_id' => auth()->user()->team_id,
                ];
                if ($bookingItem->booking_fee) {
                    $bookingItem->booking_fee->update($bookingFeeData);
                } else {
                    BookingFee::create($bookingFeeData);
                }

                $this->createBookingNotes($booking, $bookingItem, $validated);
                $this->logAudit('Booking updated', $booking, $bookingItem, [
                    'service_id' => $bookingItem->service_id,
                    'season_id' => $bookingItem->season_id,
                    'amount' => $amount,
                ]);

                if (is_array($validated['discount_lines']) && $validated['discount_lines'] != null) {
                    $booking_discount = BookingDiscount::updateOrCreate(
                        [
                            'booking_id' => $booking->id,
                        ],
                        [
                            'final_price_adult' => (int) ($validated['adult'] ?? 0),
                            'final_price_child' => (int) ($validated['child'] ?? 0),
                            'final_price_guide' => (int) ($validated['guide'] ?? 0),
                        ]
                    );
                    $booking_discount->booking_discount_items()->delete();
                    foreach ($validated['discount_lines'] as $line) {
                        $booking_discount->booking_discount_items()->updateOrCreate(
                            [
                                'pax_type' => $line['type'],
                                'discount_id' => $line['discount_id'],
                            ],
                            [
                                'count' => $line['count'],
                                'base_rate' => $line['base_rate'],
                                'final_rate' => $line['final_rate'],
                            ]
                        );
                    }
                }

                $invoiceTypeId = $this->getTypeInvoiceId(TypeInvoice::SLUG_STD);
                $existingInvoiceIds = Invoice::withTrashed()
                    ->where('booking_id', $booking->id)
                    ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
                    ->pluck('id');
                if ($existingInvoiceIds->isNotEmpty()) {
                    InvoiceItem::whereIn('invoice_id', $existingInvoiceIds)->delete();
                    Invoice::whereIn('id', $existingInvoiceIds)->delete();
                }

                File::where('booking_id', $booking->id)
                    ->where('table_name', 'BookingItem')
                    ->where('type_file_id', 6)
                    ->delete();

                if ($existingInvoiceIds->isNotEmpty()) {
                    File::where('booking_id', $booking->id)
                        ->where('table_name', 'invoices')
                        ->where('type_file_id', 4)
                        ->whereIn('table_row_id', $existingInvoiceIds)
                        ->delete();
                }

                // TODO: re-enable template-driven invoices when clients are ready.
                $template = InvoiceTemplate::where('is_default', true)
                    ->where('type_invoice_id', $invoiceTypeId)
                    ->where('team_id', auth()->user()->team_id)
                    ->first();
                $amountExVat = round(($amount / 1.15), 2);
                $vatAmount = round(($amount - $amountExVat), 2);
                $invoice = Invoice::create([
                    'booking_id'     => $booking->id,
                    'gift_voucher_id'    => null,
                    'invoice_date'   => $bookingDate,
                    'due_date'       => $bookingDate,
                    'subtotal'       => $amountExVat,
                    'vat'            => $vatAmount,
                    'total'          => $amount,
                    'status'         => 'draft',
                    'invoice_template_id'    => $template?->id,
                    'type_invoice_id' => $invoiceTypeId,
                    'team_id'        => auth()->user()->team_id,

                ]);

                $invoiceItems = InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'pax_adults'     => (int) ($validated['adults'] ?? 0),
                    'pax_children'   => (int) ($validated['children'] ?? 0),
                    'pax_guides'     => (int) ($validated['guides'] ?? 0),
                    'price_adult'    => (int) ($validated['adult'] ?? 0),
                    'price_child'    => (int) ($validated['child'] ?? 0),
                    'price_guide'    => (int) ($validated['guide'] ?? 0),
                    'team_id'        => auth()->user()->team_id,
                    'service_id'     => $season->service_id,
                    'season_id'      => $season->id,
                    'booking_inventory_id' => $bookingInventory?->id,
                    'promotion_id'   => $promotion ? $promotion->id : null,
                    'wholesaler_id'  => $validated['wholesaler_id'],
                    'discount_id'    => null,
                ]);

                $renderService = app(RendererService::class);
                $data = $renderService->invoice($booking, $customer, $invoice);
                $invoicePdf = Pdf::loadView('invoices.invoice', $data);
                $invoicePdf->setPaper('A4', 'portrait');

                $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
                Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());
                $documentLabel = $data['document']['label'] ?? 'Invoice';
                $documentLabelLower = strtolower($documentLabel);
                $reminderContent = !empty($validated['email_reminder'])
                    ? "<p><strong>Reminder</strong></p><p>{$validated['email_reminder']}</p>"
                    : '';
                $content = "
                        <p>Hello {$customer->full_name},</p>
                        <p>Your booking has been updated. Please review your updated invoice.</p>
                        <p>Thank you for choosing Canopy Tours.</p>
                        <p>Your booking reference is {$booking->reference}.</p>
                        {$reminderContent}
                        <p>Please contact us if you have any questions.</p>";
                if ($autoSendInvoice) {
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $validated['lead_email'],
                        'subject'        => $documentLabel . ' ' . $data['invoice']['number'],
                        'body'           => strip_tags($content),
                        'attachment_path' => $invoiceFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => auth()->user()->id,
                        'auditable_type' => 'App\Models\Booking',
                        'auditable_id'   => $booking->id,
                        'team_id'        => auth()->user()->team_id,
                    ]);
                    $this->logAudit('Email sent (invoice)', $booking, $bookingItem, [
                        'subject' => $documentLabel . ' ' . $data['invoice']['number'],
                        'receiver' => $validated['lead_email'],
                        'attachment' => $invoiceFilename,
                    ]);
                }
                File::create([
                    'team_id'      => $invoice->team_id,
                    'table_name'   => 'invoices',
                    'table_row_id' => $invoice->id,
                    'file_name'    => $data['invoice']['number'] . '.pdf',
                    'file_type'    => "application/pdf",
                    'type_file_id' => 4,
                    'path'         => $invoiceFilename,
                    'booking_id'   => $booking->id,
                ]);
                if ($autoSendQuote) {
                    $quoteTypeId = $this->getTypeInvoiceId(TypeInvoice::SLUG_QUOTE);
                    // TODO: re-enable template-driven quotes when clients are ready.
                    $quoteTemplate = InvoiceTemplate::where('is_default', true)
                        ->where('type_invoice_id', $quoteTypeId)
                        ->where('team_id', auth()->user()->team_id)
                        ->first();
                    $quoteInvoice = Invoice::create([
                        'booking_id'     => $booking->id,
                        'gift_voucher_id'    => null,
                        'invoice_date'   => $bookingDate,
                        'due_date'       => $bookingDate,
                        'subtotal'       => $amountExVat,
                        'vat'            => $vatAmount,
                        'total'          => $amount,
                        'status'         => 'draft',
                        'invoice_template_id'    => $quoteTemplate?->id,
                        'type_invoice_id' => $quoteTypeId,
                        'team_id'        => auth()->user()->team_id,
                    ]);

                    InvoiceItem::create([
                        'invoice_id'     => $quoteInvoice->id,
                        'pax_adults'     => (int) ($validated['adults'] ?? 0),
                        'pax_children'   => (int) ($validated['children'] ?? 0),
                        'pax_guides'     => (int) ($validated['guides'] ?? 0),
                        'price_adult'    => (int) ($validated['adult'] ?? 0),
                        'price_child'    => (int) ($validated['child'] ?? 0),
                        'price_guide'    => (int) ($validated['guide'] ?? 0),
                        'team_id'        => auth()->user()->team_id,
                        'service_id'     => $season->service_id,
                        'season_id'      => $season->id,
                        'booking_inventory_id' => $bookingInventory?->id,
                        'promotion_id'   => $promotion ? $promotion->id : null,
                        'wholesaler_id'  => $validated['wholesaler_id'],
                        'discount_id'    => null,
                    ]);

                    $quoteData = $renderService->invoice($booking, $customer, $quoteInvoice);
                    $quotePdf = Pdf::loadView('invoices.invoice', $quoteData);
                    $quotePdf->setPaper('A4', 'portrait');
                    $quoteFilename = 'quotes/' . $quoteData['invoice']['number'] . '.pdf';
                    Storage::disk('public')->put($quoteFilename, $quotePdf->output());

                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $validated['lead_email'],
                        'subject'        => ($quoteData['document']['label'] ?? 'Quote') . ' ' . $quoteData['invoice']['number'],
                        'body'           => strip_tags($content),
                        'attachment_path' => $quoteFilename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => auth()->user()->id,
                        'auditable_type' => 'App\Models\Booking',
                        'auditable_id'   => $booking->id,
                        'team_id'        => auth()->user()->team_id,
                    ]);
                    $this->logAudit('Email sent (quote)', $booking, $bookingItem, [
                        'subject' => ($quoteData['document']['label'] ?? 'Quote') . ' ' . $quoteData['invoice']['number'],
                        'receiver' => $validated['lead_email'],
                        'attachment' => $quoteFilename,
                    ]);

                    File::create([
                        'team_id'      => $quoteInvoice->team_id,
                        'table_name'   => 'invoices',
                        'table_row_id' => $quoteInvoice->id,
                        'file_name'    => $quoteData['invoice']['number'] . '.pdf',
                        'file_type'    => "application/pdf",
                        'type_file_id' => 4,
                        'path'         => $quoteFilename,
                        'booking_id'   => $booking->id,
                    ]);

                    Mail::to($customer->email_address)->send(new BookingMail($quoteData, [
                        [
                            'data' => $quotePdf->output(),
                            'name' => strtolower($quoteData['document']['label'] ?? 'quote') . "-{$quoteData['invoice']['number']}.pdf",
                            'mime' => 'application/pdf',
                        ],
                    ]));
                }

                $bookingItem = BookingItem::with(
                    'booking',
                    'booking.team',
                    'booking.team.file_logo',
                    'customers',
                    'booking_inventory.inventory_time_slot',
                    'booking_inventory.inventory_type_day.type_day',
                    'booking_fee'
                )->where('booking_id', $booking->id)->first();

                $passData = $this->getPassDetails($bookingItem);
                $html = view('pdfs.boarding-pass', ['data' => $passData])->render();
                $dir = "pass/{$bookingItem->id}";
                $fileName = "{$dir}/boarding_pass_{$bookingItem->id}.pdf";
                $disk = Storage::disk('public');
                $fullPath = $disk->path($fileName);
                if (!$disk->exists($fileName)) {
                    $disk->makeDirectory($dir);
                    $chromePath = config('browsershot.chrome_path');
                    if (empty($chromePath)) {
                        Log::warning('Browsershot chrome path not resolved; using default detection.', [
                            'env_path' => env('BROWSERSHOT_CHROME_PATH'),
                        ]);
                    }
                    $shot = Browsershot::html($html);
                    if (!empty($chromePath)) {
                        $shot->setChromePath($chromePath);
                    }
                    $shot->format('A4')
                        ->showBackground()
                        ->margins(0, 0, 0, 0)
                        ->scale(1.0)
                        ->timeout(60000)
                        ->save($fullPath);
                }

                if ($autoSendInvoice) {
                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $customer->email_address,
                        'subject'        => 'Your Boarding Pass – ' . $documentLabel . ' ' . $data['invoice']['number'],
                        'body'           => strip_tags($content),
                        'attachment_path' => $fileName,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => auth()->user()->id,
                        'auditable_type' => BookingItem::class,
                        'auditable_id'   => $bookingItem->id,
                        'team_id'        => $bookingItem->team_id,
                    ]);
                }
                File::create([
                    'team_id'      => $bookingItem->team_id,
                    'table_name'   => 'BookingItem',
                    'table_row_id' => $bookingItem->id,
                    'file_name'    =>  $fileName,
                    'file_type'    => "application/pdf",
                    'type_file_id' => 6,
                    'path'         => $fileName,
                    'booking_id'   => $booking->id,
                ]);
                $attachments = [
                    [
                        'data' => $invoicePdf->output(),
                        'name' => "{$documentLabelLower}-{$data['invoice']['number']}.pdf",
                        'mime' => 'application/pdf',
                    ],
                    [
                        'data' => file_get_contents($fullPath),
                        'name' => $fileName,
                        'mime' => 'application/pdf',
                    ],
                ];
                $messageData = [
                    'invoice' => [
                        'number' => $booking->reference ?? 'INV-No',
                        'date' => now()->format('Y-m-d'),
                    ],
                    'customer' => [
                        'name' => $customer->full_name,
                    ],
                    'booking' => [
                        'reference' => $booking->reference,
                    ],
                ];

                if ($autoSendInvoice) {
                    Mail::to($customer->email_address)->send(new BookingMail($messageData, $attachments));
                }
            });

            $locations = TypeLocation::pluck('id', 'name');
            if (!$pickupEnabled) {
                $locations = $locations->reject(function ($id, $name) {
                    $label = strtolower((string) $name);
                    return in_array((int) $id, [1, 4], true)
                        || str_contains($label, 'pickup')
                        || str_contains($label, 'drop');
                });
            }
            $booking_infos = [];
            foreach ($locations as $key => $type_id) {
                $location = $request->input("{$key}_location");
                $time     = $request->input("{$key}_time");
                $meridiem = $request->input("{$key}_meridiem"); // Optional — only needed for pickup

                if ($location && $time) {
                    $booking_infos[] = [
                        'booking_id'       => $booking->id,
                        'type_location_id' => $type_id,
                        'location'         => $location,
                        'time'             => $time,
                        'meridiem'         => $meridiem,
                    ];
                }
            }
            foreach ($booking_infos as $info) {
                BookingInfo::updateOrCreate(
                    [
                        'booking_id' => $info['booking_id'],
                        'type_location_id' => $info['type_location_id'],
                    ],
                    $info
                );
            }

            return redirect()
                ->route('admin.bookings.index')
                ->with('success', "Booking {$booking->reference} updated successfully.");
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Booking update failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);

            return back()->with([
                'error' => 'Something went wrong while updating this booking. Please try again.',
            ])->withInput();
        }
    }

    public function getPassDetails($bookingItem)
    {
        // Mirror PaymentController boarding pass data for internal emails
        $seatsBooked = $bookingItem?->booking_inventory?->pax ?? 0;
        $basePassData = [
            'flightName' => $bookingItem->booking->team->name,
            'location' => optional($bookingItem->service)->name ?? $bookingItem->booking->team->name,
            'address' => '',
            'passenger' => $bookingItem->booking->lead_name,
            'date' => $bookingItem->booking_inventory->date,
            'time' => optional($bookingItem->booking_inventory->inventory_time_slot)->start_time,
            'seat' => $bookingItem->booking->reference,
            'seats_booked' => $seatsBooked,
            'boarding_time' => optional($bookingItem->booking_inventory->inventory_time_slot)->start_time,
            'booking_no' => $bookingItem->booking->reference,
            'flight' => $bookingItem->booking->team->name,
            'passenger_name' => $bookingItem->booking->lead_name,
            'footer' => '',
            'sections' => [],
        ];

        $teamId = $bookingItem->booking->team_id;
        $serviceId = $bookingItem->service_id;
        $seasonId = $bookingItem->season_id;

        $template = null;
        if ($teamId) {
            $template = BoardingPassTemplate::with(['sections' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
                ->where('team_id', $teamId)
                ->where('is_active', true)
                ->when($serviceId, fn($q) => $q->where(function ($qq) use ($serviceId) {
                    $qq->whereNull('service_id')->orWhere('service_id', $serviceId);
                }))
                ->when($seasonId, fn($q) => $q->where(function ($qq) use ($seasonId) {
                    $qq->whereNull('season_id')->orWhere('season_id', $seasonId);
                }))
                ->orderByRaw('season_id is null')
                ->orderByRaw('service_id is null')
                ->first();

            if (!$template && !$serviceId && !$seasonId) {
                $template = BoardingPassTemplate::with(['sections' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                    ->where('team_id', $teamId)
                    ->where('is_active', true)
                    ->whereNull('service_id')
                    ->whereNull('season_id')
                    ->where('is_default', true)
                    ->first();
            }

            if (!$template && !$serviceId && !$seasonId) {
                $this->alertMissingBoardingPassTemplate($teamId, $bookingItem);
            }
        }

        if ($template) {
            $sections = $template->sections
                ->map(function ($section) {
                    return [
                        'title' => $section->title,
                        'icon' => $section->icon_path,
                        'items' => is_array($section->items) ? $section->items : [],
                    ];
                })
                ->values()
                ->all();

            return array_merge($basePassData, [
                'location' => $template->location ?? $basePassData['location'],
                'address' => $template->address ?? '',
                'footer' => $template->footer ?? '',
                'sections' => $sections,
            ]);
        }

        switch ($bookingItem->booking->team->name) {
            case 'Tsitsikamma':
                return array_merge($basePassData, [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Tsitsikamma Canopy Tour',
                    'address' => 'Tsitsikamma Indigenous Forest, Storms River',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' => $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'footer' => 'Please note:  A Canopy Tour is an adventure activity and does come with risks. Although every effort is taken by the Canopy Tours and our staff to ensure your safety, we request that you please follow all instructions given to you by our staff either in the briefing or on the system itself.  Any non compliance to safety and or rules and polocies may result in a person being removed from the tour at anytime, at which time no refund will be given.',
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A short 4x4 trip to the drop off point.',
                                'A 2.5 - 3 hour fully guided forest canopy zipline adventure along 10 elevated platforms.',
                                'Small exclusive groups of 8–10 people.',
                                '2x fully trained Canopy Tour facilitators will join you on each tour.'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at 20 min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'Once the safety briefing begins late guests cannot join and the tour will be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Rain jacket or rain poncho on rainy days.',
                                'A small pouch or small sling bag for your phone, should you want to take photos.'
                            ]
                        ],
                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are still run in the rain and will only be stopped/cancelled if lightning in the near vicinity.',
                                'Notify the team 24 hours prior to your tour if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 120 kg per person.',
                                'Limit is based on harness size and strap circumference. (Mens size 44 jean should fit)'
                            ]
                        ],
                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer 2 tandems per tour.',
                                'Tandems for guests under 50 kg only.',
                                'Email: adventure@gardenroute.co.za for children under 9 years.',
                                'Minimum age: 5 years old.'
                            ]
                        ],
                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'Extra snacks and drinks available before and after your tour.',
                                'Wi-Fi available at the base.',
                                'Bring a card — no cash accepted.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                '100% refund for cancellations 24+ hours before the tour.',
                                'No refund for late arrivals or missed tours.',
                                'If the company cancels, you receive a full refund.',
                                'Third-party refunds handled through the agent.'
                            ]
                        ],
                    ]
                ]);
            case 'Drakensberg':
                return array_merge($basePassData, [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Drakensberg Canopy Tour',
                    'address' => 'Drakensberg Sun Property, R160 off the R600, Cathkin Park Central Drakensberg, 3340',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' =>  $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'footer' => 'Please note:  A Canopy Tour is an adventure activity and does come with risks. Although every effort is taken by the Canopy Tours and our staff to ensure your safety, we request that you please follow all instructions given to you by our staff either in the briefing or on the system itself.  Any non compliance to safety and or rules and polocies may result in a person being removed from the tour at anytime, at which time no refund will be given.',
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A 2,5km 4x4 drive, from base to the start point and from our finish point back to base.',
                                'A 2.5 - 3 hour fully guided forest canopy zipline adventure along 11 elevated ziplines.',
                                'Groups of up to 10 people at a time, however bigger groups can be accommodated by arrangement.',
                                '2x fully trained Drakensberg Canopy Tour facilitators will join you on each tour to ensure your safe and well looked after.',
                                'A bottle of water and a KitKat to keep you hydrated and your energy levels up.'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 20 - 30min BEFORE your tour time.',
                                'Please ensure you arrive by the requested time, as late guests may be disappointed when we are unable to accommodate them.',
                                'Once the safety briefing begins late guests might not be able to join and the tour could be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Comfortable, weather appropriate clothing and closed walking shoes.',
                                'Rain jacket on rainy days.',
                                'You may bring your phone (at own risk). Please ensure you have zip up pockets or ask at reception for options.'
                            ]
                        ],
                        [
                            'title' => 'CBD Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'If booked, be ready at your transfer departure time.',
                                'Transfers are operated by an external supplier.',
                                'Contact us if you have not been contacted by the transfer driver.'
                            ]
                        ],
                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are mildly weather-dependent but operate in rain or shine.',
                                'The weather is monitored closely — tours may be postponed or cancelled due to severe weather and specifically if there is lightning in the immediate area.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 120 -130kg kg per person.',
                                'Limit is based on harness size and strap circumference. (Mens size 44 jean should fit)',
                                'If over 120–130 kg but fit your body shape may still allow safe harness fit.',
                                'Minimum age: 6 years old. Management reserves the right to decline a child which they think, for safety reasons should not do the Canopy Tour, regardless of age.',
                                'Anyone under the influence or suspected to be under the influence of alcohol are not allowed to participate in a Canopy Tour.',
                                'Pregnant or suspected pregnant women are not allowed to do a Canopy Tour.'
                            ]
                        ],
                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                ''
                            ]
                        ],
                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Extra snacks and drinks available before and after your tour.',
                                'We accept card and cash payments on site.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                'NO REFUNDS WILL BE GIVEN FOR THE FOLLOWING REASONS:',
                                '- No-Shows',
                                '- Not completing the tour or deciding last minute not to zipline',
                                '- Any cancellations made within 24 hours of the tour',
                                '- Late arrivals',
                                '- Postponements within 24 hours of the tour'
                            ]
                        ],
                    ]
                ]);
            case 'Test Team':
                return array_merge($basePassData, [
                    'flightName' => $bookingItem->booking->team->name,
                    'location' => 'Cape Canopy Tour',
                    'address' => 'Address Placeholder',
                    'passenger' => $bookingItem->booking->lead_name,
                    'date' =>  $bookingItem->booking_inventory->date,
                    'time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'seat' => $bookingItem->booking->reference,
                    'boarding_time' => $bookingItem->booking_inventory->inventory_time_slot->start_time,
                    'booking_no' => $bookingItem->booking->reference,
                    'flight' => $bookingItem->booking->team->name,
                    'passenger_name' => $bookingItem->booking->lead_name,
                    'sections' => [
                        [
                            'title' => 'Tour Includes',
                            'icon' => '/images/icons/tour.png',
                            'items' => [
                                'All equipment and training.',
                                'A return 4x4 experience from through the nature reserve.',
                                'A 4–5 hour fully guided mountain top zipline adventure along 13 elevated platforms.',
                                'Small exclusive groups of 8–9 people.',
                                '2x fully trained Cape Canopy Tour facilitators will join you on each tour.',
                                'A personalized video (this is available to purchase as an optional extra).'
                            ]
                        ],
                        [
                            'title' => 'Arrival',
                            'icon' => '/images/icons/location.png',
                            'items' => [
                                'Please arrive at least 10–15min BEFORE your tour time.',
                                'Allow sufficient travel time — the tour will start promptly and late guests cannot be accommodated.',
                                'If coming from Cape Town, please factor in an extra 30–45min for irregular traffic delays.',
                                'Once the safety briefing begins, late guests cannot join and the tour will be considered missed.'
                            ]
                        ],
                        [
                            'title' => 'What to Bring',
                            'icon' => '/images/icons/tshirt.png',
                            'items' => [
                                'Warm clothes — mountain weather is unpredictable.',
                                'Sunblock & Hat; rain jacket on rainy days.',
                                'A small light backpack (optional).',
                                'You may bring your phone or camera (at own risk).'
                            ]
                        ],
                        [
                            'title' => 'CBD Transfers',
                            'icon' => '/images/icons/car.png',
                            'items' => [
                                'If booked, be ready at your transfer departure time.',
                                'Transfers are operated by an external supplier.',
                                'Contact us if you have not been contacted by the transfer driver.'
                            ]
                        ],
                        [
                            'title' => 'Weather',
                            'icon' => '/images/icons/cloud.png',
                            'items' => [
                                'Tours are mildly weather-dependent but operate in most conditions.',
                                'The weather is monitored closely — tours may be cancelled, refunded, or rescheduled.',
                                'Weather changes quickly; contact the team if uncertain.',
                                'Notify the team 24 hours in advance if you want to move or cancel your tour.'
                            ]
                        ],
                        [
                            'title' => 'Restrictions',
                            'icon' => '/images/icons/restrictions.png',
                            'items' => [
                                'This is an adventure activity requiring moderate fitness.',
                                'Recommended weight limit: 100–110 kg per person.',
                                'Limit is based on harness size and strap circumference.',
                                'If over 100–110 kg but fit, your body shape may still allow safe harness fit.',
                                'Minimum age: 5 years old.'
                            ]
                        ],
                        [
                            'title' => 'Tandems / Children',
                            'icon' => '/images/icons/family.png',
                            'items' => [
                                'We can offer 2 tandems per tour.',
                                'Tandems for guests under 50 kg only.',
                                'Email: zipline@capecanopytour.co.za for children under 9 years.',
                                'Minimum age: 5 years old.',
                                'Children under 5 may need to ride tandem with a guide.'
                            ]
                        ],
                        [
                            'title' => 'Further Info',
                            'icon' => '/images/icons/info.png',
                            'items' => [
                                'Tips aren’t required but appreciated.',
                                'Extra snacks and drinks available before and after your tour.',
                                'Wi-Fi available at the base.',
                                'Bring a card — no cash accepted.'
                            ]
                        ],
                        [
                            'title' => 'Cancellation / Refunds',
                            'icon' => '/images/icons/refund.png',
                            'items' => [
                                '100% refund for cancellations 24+ hours before the tour.',
                                'No refund for late arrivals or missed tours.',
                                'If the company cancels, you receive a full refund.',
                                'Third-party refunds handled through the agent.'
                            ]
                        ],
                    ]
                ]);
            default:
                return $basePassData;
        }
    }

    protected function alertMissingBoardingPassTemplate(int $teamId, BookingItem $bookingItem): void
    {
        $typeAlert = TypeAlert::firstOrCreate(
            ['slug' => 'boarding_pass_template_missing'],
            ['name' => 'Boarding Pass Template Missing']
        );

        $alertText = sprintf(
            'Boarding pass template missing (team %d, booking %d, item %d).',
            $teamId,
            $bookingItem->booking_id ?? 0,
            $bookingItem->id ?? 0
        );

        $alreadyAlerted = Alert::where('team_id', $teamId)
            ->where('type_alert_id', $typeAlert->id)
            ->where('alert_text', $alertText)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyAlerted) {
            return;
        }

        $alert = Alert::create([
            'alert_text' => $alertText,
            'alert_link' => route('admin.boardingpass.index'),
            'type_alert_id' => $typeAlert->id,
            'team_id' => $teamId,
        ]);

        $users = User::where('team_id', $teamId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Manager', 'Consultant']);
            })
            ->with('roles')
            ->get();

        foreach ($users as $user) {
            $user->alerts()->attach($alert->id);
        }
    }

    protected function logPromoDiscountVisibility(?int $serviceId, string $context): void
    {
        if (!config('app.debug') || !$serviceId) {
            return;
        }

        $today = now()->toDateString();

        $promos = Promotion::select(
            'id',
            'name',
            'promo_code',
            'service_id',
            'internal_use_only',
            'booking_start_date',
            'booking_end_date'
        )->get();

        foreach ($promos as $promo) {
            $reasons = [];
            if (empty($promo->service_id)) {
                $reasons[] = 'missing_service_id';
            } elseif ((int) $promo->service_id !== (int) $serviceId) {
                $reasons[] = 'service_mismatch';
            }
            $bookingWindowStatus = 'unknown';
            if ($promo->booking_start_date && $promo->booking_end_date) {
                if ($promo->booking_start_date > $today) {
                    $bookingWindowStatus = 'upcoming';
                } elseif ($promo->booking_end_date < $today) {
                    $bookingWindowStatus = 'expired';
                } else {
                    $bookingWindowStatus = 'active';
                }
            }

            Log::info('Promo visibility debug', [
                'context' => $context,
                'service_id' => $serviceId,
                'promo_id' => $promo->id,
                'promo_code' => $promo->promo_code,
                'promo_name' => $promo->name,
                'promo_service_id' => $promo->service_id,
                'internal_use_only' => (bool) $promo->internal_use_only,
                'booking_window' => [
                    'start' => $promo->booking_start_date,
                    'end' => $promo->booking_end_date,
                ],
                'booking_window_status' => $bookingWindowStatus,
                'admin_status' => empty($reasons) ? 'included' : 'excluded',
                'admin_reasons' => $reasons,
            ]);
        }

        $discounts = Discount::with('services:id')
            ->select('id', 'name', 'applies_to')
            ->get();

        foreach ($discounts as $discount) {
            $serviceIds = $discount->services->pluck('id')->map(fn($id) => (int) $id)->all();
            $reasons = [];
            if (empty($serviceIds)) {
                $reasons[] = 'no_services';
            }
            if (!empty($serviceIds) && !in_array((int) $serviceId, $serviceIds, true)) {
                $reasons[] = 'service_mismatch';
            }

            Log::info('Discount visibility debug', [
                'context' => $context,
                'service_id' => $serviceId,
                'discount_id' => $discount->id,
                'discount_name' => $discount->name,
                'applies_to' => $discount->applies_to,
                'discount_service_ids' => $serviceIds,
                'status' => empty($reasons) ? 'included' : 'excluded',
                'reasons' => $reasons,
            ]);
        }
    }

    public function editOld(Request $request, Booking $booking)
    {
        //abort_if(Gate::denies('booking_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $user = auth()->user();

        $booking->load([
            'booking_payments', // :id,booking_id,amount
        ])->get();

        $booking_items = BookingItem::with([
            'service',
            'season',
            'inventory',
            'customers', // :id,name,email,contact_number
            'customer_manifests.customer.booking_questionnaire', // :id,position,notes,is_indemnity,customer_id,booking_id\
            'booking_infos', // :id,booking_id,type_location_id,location,time,created_at,updated_at,deleted_at
            'customers.booking_questionnaire', // :id,booking_id,customer_id,guide_id,safety_guide_id,type_group_id,fullname,email,signature,birth_date,type_referral,country_id,province_id,is_medical,medical_detail,is_fit,is_agree
            'booking_detail', //:id,booking_id ,start_time,end_time
            'booking_fee', //:id,booking_id,tot_amount
            'booking_inventory', // :id,booking_id,inventory_id
            'booking_inventory.inventory_time_slot',
            'booking_staff', // :id,booking_id,user_id
            'booking_staff.user', // :id,name,email,contact_number
        ])->where('booking_id', $booking->id)->first();

        $users = User::select('name', 'id')->get();
        $today = now()->toDateString();
        $status_bookings = TypeBooking::select('name', 'id')->get();
        $wholesalers = Wholesaler::select('id', 'name')->get();
        //$services = Service::select('id', 'name')->get();
        $services = Service::select('id', 'name', 'allow_internal_capacity_override', 'duration', 'capacity')
            ->whereHas('seasons', function ($query) use ($today) {
                $query->whereDate('end_date', '>=', $today);
            })
            ->with([
                'seasons' => function ($query) use ($today) {
                    $query->select('id', 'name', 'start_date', 'end_date', 'service_id', 'rake_rate_adult', 'rake_rate_child', 'rake_rate_guide', 'allow_internal_price_override', 'status')
                        ->whereDate('end_date', '>=', $today);
                },
                'seasons.inventory',
                'seasons.inventory.inventory_time_slots',
            ])
            ->get();
        //$seasons = Season::select('id', 'name', 'service_id')->get();
        $seasons = Season::select('start_date', 'id')->get();
        // $inventories = Inventory::select('start_date', 'id')->get();
        $type_affiliates = TypeReferral::select('name', 'id')->get();
        $updated_bies = User::select('name', 'id')->get();
        $customers = Customer::select('full_name as name', 'id')->get();

        $viewMode = $request->input('view_mode', 'week');
        $startDate = Carbon::parse($request->input('start_date', now()));
        $timeslots = Booking::TIME_SLOTS;

        $schedule = [];
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7
        };
        $endDate = (clone $startDate)->addDays($days - 1);

        $vouchers = Voucher::select('id', 'voucher_code as name', 'amount', 'note', 'expire_date', 'season_id', 'service_id')->with('season', 'service')->get();
        $discounts = Discount::select('id', 'name', 'applies_to', 'percent_off')->with('services')->get();
        $wholesaler_rates = WholesalerRate::select('id', 'wholesaler_id', 'season_id', 'price_adult', 'price_child', 'price_guide')->with('season', 'wholesaler')->get();
        $promos = Promotion::select('id', 'name', 'promo_code', 'type', 'value', 'service_id', 'booking_start_date', 'booking_end_date', 'activity_start_date', 'activity_end_date')
            ->where('booking_start_date', '<=', $today)
            ->where('booking_end_date', '>=', $today)
            ->where('internal_use_only', false)
            ->with('service')
            ->get();
        $lookups = LookupItem::defaultsForTeam(auth()->user()->team_id);

        return Inertia::render('Admin/Bookings/Edit', [
            'wholesalers' => $wholesalers,
            'booking_items' => $booking_items,
            'booking' => $booking,
            'services' => $services,
            'inventories' => $seasons,
            'type_affiliates' => $type_affiliates,
            'updated_bies' => $updated_bies,
            'users' => $users,
            'status_bookings' => $status_bookings,
            'schedule' => $schedule,
            'start_date' => $startDate->toDateString(),
            'view_mode' => $viewMode,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'adults' => 0,
            'children' => 0,
            'guides' => 0,
            'availableTimes' => [],
            'vouchers' => $vouchers,
            'discounts' => $discounts,
            'wholesaler_rates' => $wholesaler_rates,
            'promos' => $promos,
            'lookups' => $lookups,
        ]);




        // $users = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $status_bookings = StatusBooking::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $services = Service::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $seasons = Season::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $inventories = Inventory::pluck('start_date', 'id')->prepend(trans('global.pleaseSelect'), '');


        // $updated_bies = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        // $customers = Customer::pluck('full_name as name', 'id');

        // $booking->load('user', 'status_booking', 'service', 'season', 'inventory', 'updated_by', 'customers', 'team');

        // return view('admin.bookings.edit', compact('booking', 'customers', 'inventories', 'seasons', 'services', 'status_bookings', 'updated_bies', 'users'));
    }

    private function createBookingNotes(Booking $booking, BookingItem $bookingItem, array $validated): void
    {
        $noteFields = [
            'invoice_note' => 'invoice_note',
            'email_reminder' => 'email_reminder',
            'booking_note' => 'booking_note',
        ];

        foreach ($noteFields as $field => $type) {
            $note = trim((string) ($validated[$field] ?? ''));
            if ($note === '') {
                continue;
            }

            BookingNote::create([
                'booking_id' => $booking->id,
                'booking_item_id' => $bookingItem->id,
                'type' => $type,
                'note' => $note,
                'created_by_id' => auth()->id(),
            ]);
        }
    }

    public function addNote(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer',
            'booking_item_id' => 'required|integer',
            'note' => 'required|string',
        ]);

        BookingNote::create([
            'booking_id' => $validated['booking_id'],
            'booking_item_id' => $validated['booking_item_id'],
            'type' => 'booking_note',
            'note' => trim($validated['note']),
            'created_by_id' => auth()->id(),
        ]);

        $booking = Booking::find($validated['booking_id']);
        $bookingItem = BookingItem::find($validated['booking_item_id']);
        if ($booking) {
            $this->logAudit('Booking note added', $booking, $bookingItem, [
                'note' => trim($validated['note']),
            ]);
        }

        return back()->with('success', 'Booking note added.');
    }
    public function updateOld(UpdateBookingRequest $request, Booking $booking)
    {
        //dd($request->all());
        //$booking->update($request->all());
        $locations = TypeLocation::pluck('id', 'name');
        $booking_infos = [];
        foreach ($locations as $key => $type_id) {
            $location = $request->input("{$key}_location");
            $time     = $request->input("{$key}_time");
            $meridiem = $request->input("{$key}_meridiem"); // Optional — only needed for pickup

            if ($location && $time) {
                // Adjust time to 24-hour if AM/PM is provided
                // if ($meridiem && in_array(strtoupper($meridiem), ['AM', 'PM'])) {
                //     $parsedTime = Carbon::createFromFormat('h:i A', "{$time} {$meridiem}");
                // } else {
                //     $parsedTime = Carbon::parse($time);
                // }

                $booking_infos[] = [
                    'booking_id'       => $booking->id,
                    'type_location_id' => $type_id,
                    'location'         => $location,
                    'time'             => $time, // ->format('H:i'),
                    'meridiem'         => $meridiem,
                ];
            }
        }
        //dd($booking_infos);
        foreach ($booking_infos as $info) {
            BookingInfo::updateOrCreate(
                [
                    'booking_id' => $info['booking_id'],
                    'type_location_id' => $info['type_location_id'],
                ],
                $info
            );
        }

        //$booking->customers()->sync($request->input('customers', []));

        return redirect()->route('admin.bookings.index');
    }


    public function add(Booking $booking)
    {
        //abort_if(Gate::denies('booking_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $user = auth()->user();

        // $booking->load([
        //     'booking_items',
        //     'booking_items.service',
        //     'booking_items.season',
        //     'booking_items.inventory',
        //     'booking_items.customers',
        //     'booking_items.customer_manifests.customer.booking_questionnaire',
        //     'booking_items.booking_infos',
        //     'booking_items.customers.booking_questionnaire',
        //     'booking_items.booking_detail',
        //     'booking_items.booking_fee',
        //     'booking_payments',
        //     'booking_items.booking_inventory',
        //     'booking_items.booking_staff',
        //     'booking_items.booking_staff.user',
        // ]);

        // $item = $booking->booking_items->get(0);
        $booking->load([
            'booking_payments',
            'files',
        ]);

        $bookingItem = BookingItem::with([
            'service',
            'season',
            'booking',
            'inventory',
            'customers',
            'customer_manifests.customer.booking_questionnaire',
            'booking_infos',
            'customers.booking_questionnaire',
            'booking_detail',
            'booking_fee',
            'booking_inventory',
            'booking_staff',
            'booking_staff.user',
        ])
            ->where('booking_id', $booking->id)
            ->orderBy('id') // or some other column for order
            ->skip(0)       // skip the first
            ->first();      // get the second

        $bookingNotes = BookingNote::with('creator:id,name')
            ->where('booking_id', $booking->id)
            ->when($bookingItem?->id, function ($query, $bookingItemId) {
                $query->where('booking_item_id', $bookingItemId);
            })
            ->where('type', 'booking_note')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'note' => $note->note,
                    'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
                    'created_by' => $note->creator?->name,
                ];
            });

        $manifest = $bookingItem->paxByBookingItem($bookingItem->id);
        if ($bookingItem) {
            $confirmOptions = TypeConfirmation::whereIn('id', [1, 2])
                ->select('id', 'name')
                ->get()
                ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                ->values()
                ->all();

            $monthNames = [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December',
            ];

            $optionMaps = [
                'type_group_id' => TypeGroup::select('id', 'name')
                    ->get()
                    ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                    ->values()
                    ->all(),
                'referral_id' => TypeReferral::select('id', 'name')
                    ->get()
                    ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                    ->values()
                    ->all(),
                'continent_id' => Continent::select('id', 'name')
                    ->get()
                    ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                    ->values()
                    ->all(),
                'country_id' => Country::select('id', 'name')
                    ->get()
                    ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                    ->values()
                    ->all(),
                'province_id' => State::select('id', 'name')
                    ->get()
                    ->map(fn($row) => ['id' => $row->id, 'name' => $row->name])
                    ->values()
                    ->all(),
                'age_id' => Questionnaire::AGES,
                'gender' => Questionnaire::GENDERS,
                'medical_id' => $confirmOptions,
                'is_fit' => $confirmOptions,
                'is_agree' => $confirmOptions,
                'marketing_opt_in' => $confirmOptions,
                'pregnant' => $confirmOptions,
                'birthday_month' => collect($monthNames)
                    ->map(fn($name, $index) => ['id' => $index + 1, 'name' => $name])
                    ->values()
                    ->all(),
                'birthday_day' => collect(range(1, 31))
                    ->map(fn($day) => ['id' => $day, 'name' => (string) $day])
                    ->values()
                    ->all(),
                'parent_id' => $bookingItem->customers
                    ->map(fn($customer) => ['id' => $customer->id, 'name' => $customer->full_name])
                    ->values()
                    ->all(),
            ];

            $resolveAnswer = function ($value, $slug) use ($optionMaps) {
                if ($value === null || $value === '') {
                    return null;
                }

                $options = $optionMaps[$slug] ?? null;
                if (!$options || !is_array($options) || count($options) === 0) {
                    return $value;
                }

                $decodedValue = $value;
                if (is_string($value)) {
                    $trimmed = trim($value);
                    if (
                        ($trimmed !== '') &&
                        ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) ||
                            (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')))
                    ) {
                        $decoded = json_decode($trimmed, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $decodedValue = $decoded;
                        }
                    }
                }

                $mapOption = function ($raw) use ($options) {
                    foreach ($options as $option) {
                        $optionId = $option['id'] ?? null;
                        if ($optionId !== null && (string) $optionId === (string) $raw) {
                            return $option['name'] ?? $raw;
                        }
                    }
                    return $raw;
                };

                if (is_array($decodedValue)) {
                    return collect($decodedValue)
                        ->map(fn($val) => $mapOption($val))
                        ->implode(', ');
                }

                return $mapOption($decodedValue);
            };

            $answersByCustomer = QuestionAnswer::with([
                'question.question_sites' => function ($query) use ($bookingItem) {
                    $query->where('service_id', $bookingItem->service_id);
                },
            ])
                ->where('booking_item_id', $bookingItem->id)
                ->get()
                ->groupBy('customer_id');

            $bookingItem->customer_manifests?->each(function ($customerManifest) use ($answersByCustomer, $resolveAnswer, $optionMaps) {
                $answers = $answersByCustomer->get($customerManifest->customer_id, collect())
                    ->map(function ($answer) use ($resolveAnswer, $optionMaps) {
                        $site = $answer->question?->question_sites?->first();
                        $slug = $answer->question?->slug;
                        $mappedOptions = $slug ? ($optionMaps[$slug] ?? null) : null;
                        return [
                            'id' => $answer->id,
                            'answer' => $answer->answer,
                            'resolved_answer' => $slug ? $resolveAnswer($answer->answer, $slug) : $answer->answer,
                            'question' => [
                                'id' => $answer->question?->id,
                                'name' => $answer->question?->name,
                                'slug' => $answer->question?->slug,
                                'type' => $answer->question?->type,
                                'options' => $mappedOptions ?? $answer->question?->options,
                            ],
                            'sort_order' => $site?->sort_order ?? 999,
                        ];
                    })
                    ->sortBy('sort_order')
                    ->values();

                $customerManifest->setAttribute('question_answers', $answers);
            });
        }
        //dd($manifest);
        $staff = User::with('roles:id,name')
            ->whereHas('roles', function ($query) {
                $query->whereIn('id', [4, 5, 6]);
            })
            ->when($user->team_id, function ($query, $teamId) {
                $query->where('team_id', $teamId);
            })
            ->select('name', 'id')
            ->get()
            ->map(function ($user) {
                $roleName = $user->roles->first()?->name ?? 'No Role';
                return collect([
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $roleName,
                ]);
            });
        // ->flatMap(function ($user) {
        //     return $user->roles->map(function ($role) use ($user) {
        //         return "{$user->name} - {$role->name}";
        //     });
        // });
        //dd($booking);
        $lookups = LookupItem::defaultsForTeam(auth()->user()->team_id);
        $invoiceIds = Invoice::where('booking_id', $booking->id)->pluck('id')->all();
        $bookingEmails = Email::where(function ($query) use ($booking, $invoiceIds) {
            $query->where(function ($sub) use ($booking) {
                $sub->where('auditable_type', 'App\Models\Booking')
                    ->where('auditable_id', $booking->id);
            });
            if (!empty($invoiceIds)) {
                $query->orWhere(function ($sub) use ($invoiceIds) {
                    $sub->where('auditable_type', 'App\Models\Invoice')
                        ->whereIn('auditable_id', $invoiceIds);
                });
            }
        })
            ->orderByDesc('sent_at')
            ->get()
            ->map(function ($email) {
                $sentAt = $email->sent_at;
                if (is_string($sentAt)) {
                    try {
                        $sentAt = Carbon::parse($sentAt);
                    } catch (\Throwable $e) {
                        $sentAt = null;
                    }
                }
                $attachmentUrl = $email->attachment_path
                    ? Storage::disk('public')->url($email->attachment_path)
                    : null;
                return [
                    'id' => $email->id,
                    'sender' => $email->sender,
                    'receiver' => $email->receiver,
                    'subject' => $email->subject,
                    'auditable_type' => $email->auditable_type,
                    'sent_at' => $sentAt?->format('Y-m-d H:i:s'),
                    'attachment_path' => $attachmentUrl,
                ];
            });

        $auditLogsQuery = AuditLog::query()
            ->where(function ($query) use ($booking, $bookingItem) {
                $query->where(function ($sub) use ($booking) {
                    $sub->where('subject_type', 'App\Models\Booking')
                        ->where('subject_id', $booking->id);
                });
                if ($bookingItem?->id) {
                    $query->orWhere(function ($sub) use ($bookingItem) {
                        $sub->where('subject_type', 'App\Models\BookingItem')
                            ->where('subject_id', $bookingItem->id);
                    });
                }
            })
            ->orderByDesc('created_at');

        $auditLogs = $auditLogsQuery->get()
            ->map(function ($log) {
                $userName = User::where('id', $log->user_id)->value('name');
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'subject_type' => $log->subject_type,
                    'subject_id' => $log->subject_id,
                    'user' => $userName,
                    'host' => $log->host,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        $settings = LookupItem::settingsForTeam($user->team_id);

        return Inertia::render('Admin/Bookings/Add', [
            'booking' => $booking,
            'manifest' => $manifest,
            'bookingItem' => $bookingItem,
            'staff' => $staff,
            'lookups' => $lookups,
            'settings' => $settings,
            'bookingNotes' => $bookingNotes,
            'bookingEmails' => $bookingEmails,
            'bookingAuditLogs' => $auditLogs,
        ]);
    }

    public function show(Booking $booking)
    {
        abort_if(Gate::denies('booking_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $booking->load('user', 'status_booking', 'service', 'season', 'inventory', 'updated_by', 'customers', 'team');

        return view('admin.bookings.show', compact('booking'));
    }

    public function destroy(Booking $booking)
    {
        // abort_if(Gate::denies('booking_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $this->logAudit('Booking cancelled', $booking, $booking->booking_items->first(), [
            'reason' => 'manual',
        ]);
        $booking_items = $booking->booking_items;
        foreach ($booking_items as $item) {
            $item->delete();
        }

        $booking->delete();

        return back();
    }

    public function cancelSlot(Request $request)
    {
        $validated = $request->validate([
            'booking_date' => 'required|date',
            'inventory_time_slot_id' => 'required|integer',
            'service_id' => 'required|integer',
        ]);

        $items = BookingItem::with('booking')
            ->where('team_id', auth()->user()->team_id)
            ->where('service_id', $validated['service_id'])
            ->whereHas('booking_inventory', function ($query) use ($validated) {
                $query->whereDate('date', $validated['booking_date'])
                    ->where('inventory_time_slot_id', $validated['inventory_time_slot_id']);
            })
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'No bookings found for that slot.');
        }

        $bookingIds = $items->pluck('booking_id')->unique()->values();

        $bookingsForAudit = Booking::whereIn('id', $bookingIds)->get()->keyBy('id');
        foreach ($items as $item) {
            $booking = $bookingsForAudit->get($item->booking_id);
            if ($booking) {
                $this->logAudit('Booking cancelled (slot)', $booking, $item, [
                    'booking_date' => $validated['booking_date'],
                    'inventory_time_slot_id' => $validated['inventory_time_slot_id'],
                    'service_id' => $validated['service_id'],
                ]);
            }
        }

        DB::transaction(function () use ($items, $bookingIds) {
            foreach ($items as $item) {
                $item->delete();
            }

            $bookings = Booking::whereIn('id', $bookingIds)->get();
            foreach ($bookings as $booking) {
                if ($booking->booking_items()->count() === 0) {
                    $booking->delete();
                }
            }
        });

        return back()->with('success', 'Bookings cancelled for that slot.');
    }

    public function sendEmail(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'required|exists:customers,id',
            'recipient' => 'required|email',
            'subject' => 'required',
            'content' => 'required',
        ]);

        $booking = Booking::findOrFail($request->input('booking_id'));
        $customer = Customer::findOrFail($request->input('customer_id'));
        $recipient = $validated['recipient'];
        $subject = $validated['subject'];
        $content = $validated['content'];
        // $subject = 'New Booking';
        // $content = '<p>Hello,</p><p>A new booking has been created for you.</p><p>Please login to your account to view the booking.</p>';
        Email::create([
            'sender'         => config('mail.from.address'),
            'receiver'       => $recipient,
            'subject'        => $subject,
            'body'           => strip_tags($content), // or a summary
            'attachment_path' => null,
            'sent'           => true,
            'sent_at'        => now(),
            'user_id'        => auth()->user()->id,
            'auditable_type' => 'App\Models\Booking',
            'auditable_id'   => $booking->id,
            'team_id'        => auth()->user()->team_id,
        ]);
        $this->logAudit('Email sent (manual)', $booking, null, [
            'subject' => $subject,
            'receiver' => $recipient,
        ]);

        Mail::to($recipient)->send(new MessageGuestMail($subject, $content));

        return redirect()->back()->with('success', 'Message sent');
    }

    public function calendar(Request $request)
    {
        // dd($request->all());
        $activityId = $request->input('activity_id');
        $viewMode   = $request->input('view_mode', 'week');
        $startDate  = Carbon::parse($request->input('start_date', now()));
        $viewMode != 'day' ? $startDate->addDays(-2) : $startDate;
        $startTime = $request->input('start_time');
        $totalRequested = (int) $request->input('adults', 0) + (int) $request->input('children', 0);
        if ($request->boolean('ignore_requested_pax')) {
            $totalRequested = 0;
        }

        $days = match ($viewMode) {
            'day'   => 1,
            'week'  => 7,
            'month' => 30,
            default => 7
        };

        $endDate = (clone $startDate)->addDays($days - 1);

        $season  = Season::findOrFail($activityId);
        $service = Service::findOrFail($season->service_id);

        $inventory = Inventory::with(['inventory_type_days', 'inventory_time_slots'])
            ->where('season_id', $season->id)
            ->first();

        // outside season bounds
        if ($startDate->lt(Carbon::parse($season->start_date)) || $endDate->gt(Carbon::parse($season->end_date))) {
            return response()->json([
                'schedule' => [],
                'days'     => collect(CarbonPeriod::create($startDate, $endDate))
                    ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                    ->values(),
                'message'  => 'Selected date is outside the inventory availability period.',
            ], 200);
        }

        // booking data
        $rawBookingData = DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
            ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->leftJoin('inventory_overrides as io', 'io.id', '=', 'bi.inventory_override_id')
            ->where('items.inventory_id', $inventory->id)
            ->where('items.team_id', auth()->user()->team_id)
            ->whereNull('b.deleted_at')
            ->whereNull('items.deleted_at')
            ->whereNull('bi.deleted_at')
            ->whereBetween('bi.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select([
                'bi.date',
                'td.name as day',
                'its.start_time as time',
                DB::raw('SUM(bi.pax) as total')
            ])
            ->groupBy('bi.date', 'td.name', 'its.start_time')
            ->get();

        $bookingData = $rawBookingData->keyBy(fn($row) => $row->date . '_' . $row->time);

        if (config('app.debug')) {
            Log::info('Calendar capacity debug', [
                'activity_id' => $activityId,
                'inventory_id' => $inventory->id,
                'season_id' => $season->id,
                'service_id' => $service->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'start_time_param' => $startTime,
                'totals_at_10' => $rawBookingData
                    ->filter(fn($row) => $row->time === '10:00:00')
                    ->values()
                    ->all(),
                'totals_all' => $rawBookingData->values()->all(),
            ]);
        }

        // allowed days from inventory


        // build schedule (still extendable with promo/closeout)
        $currentBooking = null;
        if ($request->filled('current_booking_date') && $request->filled('current_booking_time')) {
            $currentBooking = [
                'date' => $request->input('current_booking_date'),
                'time' => $request->input('current_booking_time'),
                'pax' => (int) $request->input('current_booking_pax', 0),
            ];
        }
        $schedule = app(\App\Services\Calendar\ServiceCalendarBuilder::class)
            ->build($inventory, $season, $service, $bookingData, $startDate, $endDate, $totalRequested, $startTime, $currentBooking);

        return response()->json([
            'schedule' => $schedule,
            'days'     => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'message'  => '',
        ]);
    }

    public function calendar1(Request $request)
    {
        //dd($request->all());
        $activityId = $request->input('activity_id');
        $viewMode = $request->input('view_mode', 'week');
        $startDate = Carbon::parse($request->input('start_date', now()));
        $viewMode != 'day' ? $startDate->addDays(-2) : $startDate;
        $startTime = $request->input('start_time');
        $totalRequested = (int) $request->input('adults', 0) + (int) $request->input('children', 0);
        $days = match ($viewMode) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 7
        };
        $endDate = (clone $startDate)->addDays($days - 1);
        //dd($endDate);
        $season = Season::findOrFail($activityId);
        $service = Service::findOrFail($season->service_id);



        $inventory = Inventory::with(['inventory_type_days', 'inventory_time_slots'])->where('season_id', $season->id)->first();
        //dd($inventory);
        if ($startDate->lt(Carbon::parse($season->start_date)) || $endDate->gt(Carbon::parse($season->end_date))) {
            return response()->json([
                'schedule' => [],
                'days' => [],
                'message' => 'Selected date is outside the inventory availability period.',
            ], 200); // Or 200 with flag
        }

        if (true) {
            //DB::enableQueryLog();
            $excludeBookingItemId = $request->input('booking_item_id');
            $bookingData = DB::table('bookings as b')
                ->leftJoin('booking_inventories as bi', 'bi.booking_id', '=', 'b.id')
                ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
                ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
                ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
                ->leftJoin('inventory_overrides as io', 'io.id', '=', 'bi.inventory_override_id')
                ->where('b.inventory_id', $activityId)
                ->where('b.team_id', auth()->user()->team_id)
                ->whereNull('b.deleted_at')
                ->whereNull('bi.deleted_at')
                ->whereBetween('bi.date', [$startDate->toDateString(), $endDate->toDateString()])
                ->select([
                    'bi.date',
                    'td.name as day',
                    'its.start_time as time',
                    DB::raw('SUM(bi.pax) as total')
                ])
                ->groupBy('bi.date', 'td.name', 'its.start_time')
                ->get()
                ->keyBy(fn($row) => $row->date . '_' . $row->time);
            //dd(DB::getQueryLog());
        }
        // dd($bookingData);

        //dd($inventory);
        $allowedDays = $inventory->inventory_type_days->pluck('type_day_id')->toArray();

        $closeouts = Closeout::where('service_id', $inventory->service_id)
            ->where('season_id', $inventory->season_id)
            ->get();
        //dd($closeouts);
        $blockedDates = $closeouts->flatMap(function ($c) {
            logger("Closeout: {$c->start_date} to {$c->end_date}");
            // $start = Carbon::parse($c->start_date);
            // $end = Carbon::parse($c->end_date);

            // return CarbonPeriod::create($start, $end)
            //     ->map(fn($d) => $d->format('Y-m-d'));

            $start = Carbon::parse($c->start_date);
            $end = Carbon::parse($c->end_date);

            $dates = CarbonPeriod::create($start, $end)->toArray();
            logger("Generated dates: ", $dates);

            return collect($dates)->map(fn($d) => $d->format('Y-m-d'));
        })->unique()->values()->toArray();
        logger("blocked Dates: ", $blockedDates);
        $timeSlots = $inventory->inventory_time_slots
            ->when($startTime, function ($collection, $startTime) {
                // filter the collection by start_time
                return $collection->where('id', $startTime);
            })
            ->pluck('start_time')
            ->unique()->sort();
        //dd($timeSlots);
        $schedule = [];

        foreach ($timeSlots as $time) {
            $row = [
                'time' => $time,
                'days' => []
            ];

            foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
                $label = $date->format('D d');
                $dayOfWeek = $date->dayOfWeekIso;
                $dateStr = $date->toDateString();

                if (!in_array($dayOfWeek, $allowedDays)) {
                    $row['days'][$label] = [
                        'date' => $date->format('Y-m-d'),
                        'slots' => 0,
                        'status' => 'unavailable',
                        'note' => 'Not an allowed day',
                    ];
                    continue;
                }

                if (in_array($dateStr, $blockedDates)) {
                    $row['days'][$label] = [
                        'date' => $date->format('Y-m-d'),
                        'slots' => 0,
                        'status' => 'blocked',
                        'note' => 'Blocked due to closeout',
                    ];
                    continue;
                }

                // once we have the booking data, we can calculate the remaining slots
                if (true) {
                    $dateTimeStr = $dateStr . '_' . $time;
                    $booked = $bookingData[$dateTimeStr]->total ?? 0;
                    $remaining = $service->capacity - $booked;
                    if ($remaining < $totalRequested) {
                        $row['days'][$label] = [
                            'date' => $date->format('Y-m-d'),
                            'slots' => $remaining,
                            'status' => 'full',
                            'note' => 'Fully booked',
                        ];
                    } else {
                        $row['days'][$label] = [
                            'date' => $date->format('Y-m-d'),
                            'slots' => $remaining,
                            'status' => 'available',
                            'rake_rate_adult' => $season->rake_rate_adult,
                        ];
                    }
                }
                // if we don't have the booking data, we can't calculate the remaining slots
                if (false) {
                    $row['days'][$label] = [
                        'date' => $date->format('Y-m-d'),
                        'slots' => $service->capacity,
                        'status' => 'available',
                        'rake_rate_adult' => $season->rake_rate_adult,
                    ];
                }
            }

            $schedule[] = $row;
        }
        logger("schedule: ", $schedule);


        return response()->json([
            'schedule' => $schedule,
            'days' => collect(CarbonPeriod::create($startDate, $endDate))
                ->map(fn(\Carbon\Carbon $d) => $d->format('D d'))
                ->values(),
            'message' => '',
        ]);
    }

    public function calculate(Request $request)
    {
        //return response()->json($request->all());
        $data = $request->validate([
            'service_id'   => 'required|exists:services,id',
            'season_id'    => 'required|exists:seasons,id',
            'wholesaler_id' => 'nullable|exists:wholesalers,id',
            'promo_code'   => 'nullable|string',
            'invoice_discount_id' => 'nullable|exists:discounts,id',
            'pax_groups'   => 'required|array|min:1',
            'pax_groups.*.type' => 'required|in:adult,child,guide',
            'pax_groups.*.count' => 'required|integer|min:0',
            'pax_groups.*.discount_id' => 'nullable|exists:discounts,id',
            'booking_date' => 'required|date',
        ]);

        try {
            $season = Season::findOrFail($data['season_id']);
            logger()->info('************** ' . now()->toDateString());
            logger()->info('season ' . $season->id);
            logger()->info('season ', [$season]);

            // --- Season rates with safe fallbacks for column names ---
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
                $wr = WholesalerRate::where('wholesaler_id', $data['wholesaler_id'])
                    ->where('season_id', $season->id)
                    ->first();
                if ($wr) {
                    $rates['adult'] = $wr->price_adult ?? $rates['adult'];
                    $rates['child'] = $wr->price_child ?? $rates['child'];
                    $rates['guide'] = $wr->price_guide ?? $rates['guide'];
                }
                logger()->info('wholesaler rates ' . var_export($rates, true));
            }

            // Keep a copy of base (post-wholesaler, pre-promo)
            $baseRates = $rates;

            $appliedPromo = null;
            $appliedDiscounts = [];
            $total = 0.0;

            // Enforce exclusivity: wholesaler cannot combine with promo/discounts
            if (!empty($data['wholesaler_id'])) {
                $data['promo_code'] = null;
                $data['invoice_discount_id'] = null;
                $data['pax_groups'] = array_map(function ($g) {
                    $g['discount_id'] = null;
                    return $g;
                }, $data['pax_groups']);
            }
            // Enforce exclusivity: promo cannot combine with discounts
            if (!empty($data['promo_code'])) {
                $data['invoice_discount_id'] = null;
                $data['pax_groups'] = array_map(function ($g) {
                    $g['discount_id'] = null;
                    return $g;
                }, $data['pax_groups']);
            }
            // Enforce exclusivity: invoice discount cannot combine with line-item discounts
            if (!empty($data['invoice_discount_id'])) {
                $data['pax_groups'] = array_map(function ($g) {
                    $g['discount_id'] = null;
                    return $g;
                }, $data['pax_groups']);
            }

            // Step 3: Check if promotion is applied (mutually exclusive with discounts)
            if (!empty($data['promo_code'])) {
                // $promo = Promotion::where('promo_code', $data['promo_code'])
                //     ->where('service_id', $data['service_id'])
                //     ->whereDate('booking_start_date', '<=', $data['booking_date'])
                //     ->whereDate('booking_end_date', '>=', $data['booking_date'])
                //     ->first();

                // $query = Promotion::where('promo_code', $data['promo_code'])
                //     ->where('service_id', $data['service_id'])
                //     ->whereDate('booking_start_date', '<=', now())
                //     ->whereDate('booking_end_date', '>=', now());

                // if ($promo->activity_start_date && $promo->activity_end_date) {
                //     $query->whereDate('activity_start_date', '<=', $data['booking_date'])
                //         ->whereDate('activity_end_date', '>=', $data['booking_date']);
                // }

                // $promo = $query->first();
                $today = now()->toDateString();
                $activityDate = $data['booking_date'];

                $promo = Promotion::where('promo_code', $data['promo_code'])
                    ->where('service_id', $data['service_id'])
                    // Booking period check (today is when booking is made)
                    ->whereDate('booking_start_date', '<=', $today)
                    ->whereDate('booking_end_date', '>=', $today)
                    // Activity period check (customer’s chosen date)
                    ->whereDate('activity_start_date', '<=', $activityDate)
                    ->whereDate('activity_end_date', '>=', $activityDate)
                    ->first();
                if ($promo) {
                    logger()->info('promo ' . $promo->id);
                    $appliedPromo = [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'type' => $promo->type,
                        'promo_code' => $promo->promo_code,
                        'value' => (float) $promo->value,
                        'params' => $promo->params,
                        'booking_start_date' => $promo->booking_start_date,
                        'booking_end_date'   => $promo->booking_end_date,
                        'activity_start_date' => $promo->activity_start_date,
                        'activity_end_date'  => $promo->activity_end_date,
                        'internal_use_only'  => (bool) $promo->internal_use_only,
                    ];
                    logger()->info('appliedPromo ' . var_export($appliedPromo, true));

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
                    $adultCount = collect($data['pax_groups'])->where('type', 'adult')->sum('count');
                    $childCount = collect($data['pax_groups'])->where('type', 'child')->sum('count');
                    $guideCount = collect($data['pax_groups'])->where('type', 'guide')->sum('count');
                    $totalPax  = $adultCount + $childCount;
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
                    logger()->info('totalPax ' . $totalPax);
                    logger()->info('adultCount ' . $adultCount);
                    logger()->info('childCount ' . $childCount);
                    // Apply promo rules
                    switch ($promo->type) {
                        case 'percent':
                            foreach ($rates as $k => $v) {
                                $rates[$k] = round($v - ($v * ($promo->value / 100)), 2);
                            }
                            logger()->info('percent promo applied ' . var_export($rates, true));
                            break;
                        case 'time_slot':
                            foreach ($rates as $k => $v) {
                                $rates[$k] = round($v - ($v * ($promo->value / 100)), 2);
                            }
                            break;
                        case 'fixed_value':
                            $targetTotal = max(0, $baseTotal - (float) $promo->value);
                            $applyBookingTotal($targetTotal);
                            logger()->info('promo applied (booking total) ' . var_export($rates, true));
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
                                $applyBookingTotal($paxRate);
                                logger()->info('graduated promo applied (booking total) ' . var_export($rates, true));
                            }
                            // If there’s no exact lookup, fall through to normal per-pax multiplication.
                            break;

                        case 'varied_child':
                            // Support single rule  {min_adults, child_discount_percent}
                            // and tiered rules     {tiers:[{min_adults, child_discount_percent}, ...]}
                            if (!empty($params['tiers']) && is_array($params['tiers'])) {
                                // choose best tier with min_adults <= adultCount
                                $best = collect($params['tiers'])
                                    ->filter(fn($t) => isset($t['min_adults']) && $adultCount >= (int)$t['min_adults'])
                                    ->sortByDesc('min_adults')
                                    ->first();

                                if ($best && isset($best['child_discount_percent'])) {
                                    $pct = (float) $best['child_discount_percent'];
                                    $rates['child'] = round($rates['child'] - ($rates['child'] * ($pct / 100)), 2);
                                }
                            } elseif (isset($params['min_adults'], $params['child_discount_percent'])) {
                                if ($adultCount >= (int)$params['min_adults']) {
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
                                    $targetTotal = max(0, $baseTotal - $fv);
                                    $applyBookingTotal($targetTotal);
                                }
                            }
                            logger()->info('volume_discount promo applied ' . var_export($rates, true));
                            break;
                    }
                }
            }

            // Step 4: Apply per-pax discounts (only if no promo)
            // Step 4: Totals
            if ($appliedPromo) {
                // Promo applied → no per-pax discounts
                foreach ($data['pax_groups'] as $group) {
                    $type  = $group['type'];
                    $count = (int) $group['count'];
                    $rate  = (float) ($rates[$type] ?? 0);
                    $total += $rate * $count;
                }
            } else {
                // No promo → apply per-pax discounts on top of base rates (post-wholesaler)
                foreach ($data['pax_groups'] as $group) {
                    $type  = $group['type'];
                    $count = (int) $group['count'];
                    $rate  = (float) ($baseRates[$type] ?? 0);

                    if (!empty($group['discount_id'])) {
                        $discount = Discount::find($group['discount_id']);
                        if ($discount) {
                            $rate = round($rate - ($rate * ((float)$discount->percent_off / 100)), 2);
                            $appliedDiscounts[] = [
                                'id'          => $discount->id,
                                'name'        => $discount->name,
                                'percent_off' => (float) $discount->percent_off,
                                'applied_to'  => $type,
                                'count'       => $count,
                                'final_rate'  => $rate,
                            ];
                        }
                    }

                    $total += $rate * $count;
                }
                // When NO promo, applied_rates == baseRates (because discounts are per-line)
                $rates = $baseRates;
            }

            // Apply invoice-level discount to total (no stacking)
            if (!empty($data['invoice_discount_id'])) {
                $invoiceDiscount = Discount::find($data['invoice_discount_id']);
                if ($invoiceDiscount) {
                    $total = round($total - ($total * ((float)$invoiceDiscount->percent_off / 100)), 2);
                    $appliedDiscounts[] = [
                        'id'          => $invoiceDiscount->id,
                        'name'        => $invoiceDiscount->name,
                        'percent_off' => (float) $invoiceDiscount->percent_off,
                        'applied_to'  => 'invoice',
                        'count'       => null,
                        'final_rate'  => null,
                    ];
                }
            }

            $result = [
                'base_rates'         => $baseRates,         // post-wholesaler, pre-promo
                'applied_rates'      => $rates,             // post-promo (or == base if no promo)
                'applied_promotion'  => $appliedPromo,      // null if none
                'applied_discounts'  => $appliedDiscounts,  // [] if promo applied
                'total_price'        => round($total, 2),
            ];
            //dd($result);
            return response()->json($result);
        } catch (\Throwable $e) {
            Log::error('Pricing calculation failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'input'   => $request->all(),
            ]);
            return response()->json([
                'error' => 'Calculation failed.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function calculate1(Request $request)
    {
        //dd($request->all());
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'season_id' => 'required|exists:seasons,id',
            'wholesaler_id' => 'nullable|exists:wholesalers,id',
            'promo_code' => 'nullable|string',
            'booking_date' => 'required|date',
            // pax_groups: [{ type: "adult", count: 2, discount_id: null }, ...]
            'pax_groups' => 'required|array|min:1',
            'pax_groups.*.type' => 'required|in:adult,child,guide',
            'pax_groups.*.count' => 'required|integer|min:0',
            'pax_groups.*.discount_id' => 'nullable|exists:discounts,id',
        ]);

        $season = Season::findOrFail($data['season_id']);

        // Step 1: Base rack rates
        $rates = [
            'adult' => $season->price_adult,
            'child' => $season->price_child,
            'guide' => $season->price_guide,
        ];

        // Step 2: Override with wholesaler rates if set
        if (!empty($data['wholesaler_id'])) {
            $wr = WholesalerRate::where('wholesaler_id', $data['wholesaler_id'])
                ->where('season_id', $season->id)
                ->first();
            if ($wr) {
                $rates['adult'] = $wr->price_adult ?? $rates['adult'];
                $rates['child'] = $wr->price_child ?? $rates['child'];
                $rates['guide'] = $wr->price_guide ?? $rates['guide'];
            }
        }

        $appliedDiscounts = [];
        $appliedPromo = null;
        $total = 0;

        // Step 3: Apply promotion (mutually exclusive with discounts)
        if (!empty($data['promo_code'])) {
            $promo = Promotion::where('promo_code', $data['promo_code'])
                ->where('service_id', $data['service_id'])
                ->whereDate('booking_start_date', '<=', $data['booking_date'])
                ->whereDate('booking_end_date', '>=', $data['booking_date'])
                ->first();
            //dd($promo);
            if ($promo) {
                $appliedPromo = $promo;
                $params = $promo->params ? json_decode($promo->params, true) : [];
                $paxCount = collect($data['pax_groups'])->sum('count');

                switch ($promo->type) {
                    case 'percent':
                        foreach ($rates as $k => $v) {
                            $rates[$k] = $v - ($v * ($promo->value / 100));
                        }
                        break;
                    case 'time_slot':
                        foreach ($rates as $k => $v) {
                            $rates[$k] = $v - ($v * ($promo->value / 100));
                        }
                        break;
                    case 'fixed_value':
                        // apply only to adults for simplicity
                        $rates['adult'] = max(0, $rates['adult'] - $promo->value);
                        break;

                    case 'graduated':
                        if (!empty($params['lookup'][$paxCount])) {
                            // Override total directly, skip per-pax logic
                            $total = $params['lookup'][$paxCount];
                            return response()->json([
                                'base_rates' => [
                                    'adult' => $season->price_adult,
                                    'child' => $season->price_child,
                                    'guide' => $season->price_guide,
                                ],
                                'applied_rates' => $rates,
                                'applied_discount' => null,
                                'applied_promotion' => [
                                    'id' => $promo->id,
                                    'name' => $promo->name,
                                    'type' => $promo->type,
                                    'value' => $promo->value,
                                ],
                                'total_price' => $total,
                            ]);
                        }
                        break;

                    case 'varied_child':
                        if (!empty($params['min_adults']) && !empty($params['child_discount_percent'])) {
                            $adultCount = collect($data['pax_groups'])->where('type', 'adult')->sum('count');
                            if ($adultCount >= $params['min_adults']) {
                                $rates['child'] = $rates['child'] - ($rates['child'] * ($params['child_discount_percent'] / 100));
                            }
                        }
                        break;

                    case 'volume_discount':
                        foreach ($params['tiers'] ?? [] as $tier) {
                            if ($paxCount >= $tier['pax']) {
                                if ($tier['type'] === 'percent') {
                                    foreach ($rates as $k => $v) {
                                        $rates[$k] = $v - ($v * ($tier['value'] / 100));
                                    }
                                } elseif ($tier['type'] === 'fixed_value') {
                                    $rates['adult'] = max(0, $rates['adult'] - $tier['value']);
                                }
                            }
                        }
                        break;
                }
            }
        }

        // Step 4: If no promo, apply per-pax discounts
        if (!$appliedPromo) {
            foreach ($data['pax_groups'] as $group) {
                $rate = $rates[$group['type']];
                if (!empty($group['discount_id'])) {
                    $discount = Discount::find($group['discount_id']);
                    if ($discount) {
                        $appliedDiscounts[] = $discount;
                        $rate -= ($rate * ($discount->percent_off / 100));
                    }
                }
                $total += $rate * $group['count'];
            }
        } else {
            // Promo applied: recalc total with adjusted promo rates
            foreach ($data['pax_groups'] as $group) {
                $rate = $rates[$group['type']];
                $total += $rate * $group['count'];
            }
        }

        return response()->json([
            'base_rates' => [
                'adult' => $season->price_adult,
                'child' => $season->price_child,
                'guide' => $season->price_guide,
            ],
            'applied_rates' => $rates,
            'applied_discounts' => array_map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
            ], $appliedDiscounts),
            'applied_promotion' => $appliedPromo ? [
                'id' => $appliedPromo->id,
                'name' => $appliedPromo->name,
                'type' => $appliedPromo->type,
                'value' => $appliedPromo->value,
            ] : null,
            'total_price' => $total,
        ]);
    }

    public function invoiceDownload(Booking $booking)
    {
        $renderService = app(RendererService::class);
        //dd($booking);
        $sessionId = session()->getId() ?? null;
        //logger()->info('sessionId inertia ' . $sessionId);
        if ($sessionId != $booking->session_id || $booking->status_payment_id != 1) {
            return redirect()->route('front.welcome');
        }

        $booking = Booking::with('team.file_logo', 'booking_items', 'booking_items.customers', 'booking_items.booking_fee')->find($booking->id);
        $teamId = $booking->team_id;

        $template = InvoiceTemplate::where('is_default', true)
            ->where('type_invoice_id', 1) // invoice
            ->where('team_id', $teamId)
            ->first();

        if (!$booking) {
            $voucher = GiftVoucher::where('session_id', $sessionId)->where('status_payment_id', 1)->first();
            $customer = collect([
                'full_name'     => $voucher?->full_name,
                'email_address' => $voucher?->email_address,
                'contact_number' => $voucher?->contact_number,
            ]);
            $count = $voucher?->gift_voucher_items?->count();
            $items = $voucher?->gift_voucher_items->first();
            $teamId = $voucher?->booking_id;
        } else {
            $customer = Customer::find($booking?->customer_id);
            $items = $booking?->booking_items->first();
            $count = $booking?->booking_items?->count();
        }


        //  Load Blade view with data
        $invoiceTypeId = $this->getTypeInvoiceId(TypeInvoice::SLUG_STD);
        $invoice = Invoice::where('booking_id', $booking->id)
            ->when($invoiceTypeId, fn($query) => $query->where('type_invoice_id', $invoiceTypeId))
            ->first();
        $data = $renderService->invoice($booking, $customer, $invoice);
        $invoicePdf = Pdf::loadView('invoices.invoice', $data);

        // Optional: tweak DOMPDF options
        $invoicePdf->setPaper('A4', 'portrait');

        //  Download or stream PDF
        //return $pdf->download('invoice-' . $data['invoice']['number'] . '.pdf');
        return $invoicePdf->stream(); // to preview in browser

        // $html = $this->renderTemplate($template->content, $data);

        // $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        // return $pdf->stream("invoice-{$booking->id}.pdf");
        // or ->download("invoice-{$invoice->id}.pdf");
    }

    public function detailDownload(Booking $booking)
    {

        if (!$booking || $booking?->id == null) {
            return;
        }

        $bookingItem = BookingItem::with('booking', 'booking.team', 'booking.team.file_logo', 'customers', 'booking_inventory.inventory_time_slot', 'booking_inventory.inventory_type_day.type_day', 'booking_fee')
            ->where('booking_id', $booking->id)->first(); // ->get();
        $data = $this->getPassDetails($bookingItem);

        $html = view('pdfs.boarding-pass', ['data' => $data])->render();

        $dir = "pass/{$bookingItem->id}";
        $random = rand(1, 100);
        $file = "{$dir}/boarding_pass_{$random}{$bookingItem->id}.pdf";
        $disk = Storage::disk('public');
        $fullPath = $disk->path($file);
        Log::info('boarding pass ' . var_export($fullPath, true));
        Log::info('boarding pass ' . var_export($file, true));
        Log::info('boarding pass ' . var_export($dir, true));

        if (!$disk->exists($file)) {
            $disk->makeDirectory($dir);

            $chromePath = config('browsershot.chrome_path');
            if (empty($chromePath)) {
                Log::warning('Browsershot chrome path not resolved; using default detection.', [
                    'env_path' => env('BROWSERSHOT_CHROME_PATH'),
                ]);
            }
            $shot = Browsershot::html($html);
            if (!empty($chromePath)) {
                $shot->setChromePath($chromePath);
            }
            $shot->format('A4')
                ->showBackground()
                ->margins(0, 0, 0, 0)
                ->scale(1.0)
                ->timeout(60000)
                ->save($fullPath);
        }

        return response()->download($fullPath);
    }

    private function getTypeInvoiceId(string $slug): ?int
    {
        return TypeInvoice::where('slug', $slug)->value('id');
    }

    private function getDefaultInvoiceTemplate(?int $typeInvoiceId, ?int $teamId = null, bool $allowNull = false): ?InvoiceTemplate
    {
        $query = InvoiceTemplate::where('is_default', true)
            ->when($typeInvoiceId, fn($q) => $q->where('type_invoice_id', $typeInvoiceId));

        if ($teamId) {
            $template = (clone $query)->where('team_id', $teamId)->first();
            if ($template) {
                return $template;
            }
        }

        $template = $query->first();
        if ($template || $allowNull) {
            return $template;
        }

        throw new \RuntimeException('No default invoice template found.');
    }
}
