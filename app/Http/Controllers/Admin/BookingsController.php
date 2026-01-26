<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Mail\BookingMail;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\BookingFee;
use App\Models\BookingInfo;
use App\Models\BookingInventory;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\Closeout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Inventory;
use App\Models\InventoryOverride;
use App\Models\InventoryTimeSlot;
use App\Models\Promotion;
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
use App\Models\BookingDiscount;
use App\Models\BookingPricing;
use App\Models\BookingVoucher;
use App\Models\Email;
use App\Models\File;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\Team;
use App\Models\TypeBooking;
use App\Models\LookupItem;
use App\Services\RendererService;
use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BookingsController extends Controller
{
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
        ])
            ->where('team_id', auth()->user()->team_id)
            ->get();
        //dd($booking_items);
        $services = Service::with('seasons', 'seasons.inventory.inventory_time_slots')->get();

        $vatMultiplier = LookupItem::where('lookup_id', 1)->where('is_default', true)->first()->multiplier;

        $formatted = $booking_items->map(function ($booking_item) use ($vatMultiplier) {
            try {
                //dd($booking_item);
                $price = $booking_item?->booking_fee->tot_amount;
                $paid = $booking_item?->booking_fee->tot_paid;
                $due = $price - $paid;
                $service = Service::find($booking_item->service_id);
                $season = Season::find($booking_item->season_id);
                $timeSlot = InventoryTimeSlot::find($booking_item->booking_inventory->inventory_time_slot_id);

                $time = $timeSlot->start_time;
                $start_time = Carbon::parse($time);

                list($hours, $minutes, $seconds) = explode(':', $service->duration);
                $end_time = $start_time->copy()->addHours($hours)->addMinutes($minutes)->addSeconds($seconds);

                $customers = $booking_item->paxWithCustomers();
                $info = [
                    'id' => $booking_item->booking->id,
                    'reference' => $booking_item->booking->reference,
                    'lead_name' => $booking_item->booking->lead_name,
                    'lead_id' => $booking_item->booking->customer_id,
                    'bookingPax' => $booking_item->booking_inventory->pax, // $booking->customers->count(),
                    'maxPax' => $service->capacity, // $booking->customers->count(),
                    'service' => $booking_item->service->name,
                    'time' => $start_time->format('H:i:s') . ' ' . $end_time->format('H:i:s'), // optional($booking->booking_inventory)->inventory_time_slot_id ?? '' . ' - ' . optional($booking->booking_inventory)->inventory_time_slot_id ?? '',
                    'price' => round($price ?? 0, 2),
                    'paid' =>  round($paid ?? 0, 2),
                    'due' =>  round($due ?? 0, 2),
                    'customers' => $customers,
                    'dueStatus' => $due > 0 ? true : false,
                    'due_date' => $booking_item->booking->due_date,
                    'booking_date' => $booking_item->booking_inventory->date,
                    'inventory_time_slot_id' => $booking_item->booking_inventory->inventory_time_slot_id,
                    'service_id' => $booking_item->service_id,
                    'season_id' => $booking_item->season_id,
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

        $lookups = LookupItem::where('is_default', true)->get();
        $activities = Service::with('seasons')->get();
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
            ->with('seasons:id,name,start_date,end_date,service_id,rake_rate_adult,rake_rate_child,rake_rate_guide,allow_internal_price_override,status', 'seasons.inventory', 'seasons.inventory.inventory_time_slots')
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
        $lookups = LookupItem::where('is_default', true)->get();
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
            'exclusive_resource' => 'boolean', //   "exclusive_resource" => "true"

            'invoice_note' => 'string|nullable', //   "invoice_note" => "Add Invoice Note"
            'email_reminder' => 'string|nullable', //   "email_reminder" => "Add Email Reminder"
            'booking_note' => 'string|nullable', //   "booking_note" => "Add Booking Note"

            'adults' => 'required|integer|min:1', //   "adults" => "1"
            'children' => 'nullable|integer|min:0', //   "children" => "2"      
            'guides' => 'nullable|integer|min:0', //   "guides" => "2"
            'adult' => 'integer|nullable',
            'child' => 'integer|nullable',
            'guide' => 'integer|nullable',
            'rate_per_adult' => 'integer|nullable',
            'rate_per_child' => 'integer|nullable',
            'rate_per_guide' => 'integer|nullable',

            'is_wholesaler' => 'boolean', //   "is_wholesaler" => "true"
            'wholesaler_id' => 'integer|nullable', //   "wholesaler_id" => "1"
            'discount_lines' => 'array|nullable',
            'promoCode' => 'nullable|string',
            'redeem_voucher' => 'string|nullable', //   "redeem_voucher" => "Voucher Code"
        ]);
        try {
            $booking = DB::transaction(function () use ($validated) {
                $renderService = app(RendererService::class);
                $season_id = $validated['season_id'];
                $selected_slot = $validated['selected_slot'];
                //dd($selected_slot['date']);
                $season = Season::findOrFail($season_id);
                $inventory = Inventory::where('season_id', $season->id)->first();
                //$FullNameOfDay = Carbon::parse($validated['date'])->format('l'); // e.g., Monday
                //$ShortNameOfDay = Carbon::parse($validated['date'])->format('D'); // e.g., Mon
                $isoNameOfDay = Carbon::parse($selected_slot['date'])->format('N'); // iso week starts on Monday
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
                // $adultsCount = (int) ($validated['adults'] ?? 0);
                // $amount_adults = (int) ($validated['rate_per_adult'] ?? 0) * $adultsCount;
                // $childrenCount = (int) ($validated['children'] ?? 0);
                // $amount_children = (int) ($validated['rate_per_child'] ?? 0) * $childrenCount;
                // $guidesCount = (int) ($validated['guides'] ?? 0);
                // $amount_guides = (int) ($validated['rate_per_guide'] ?? 0) * $guidesCount;
                // $amount = $amount_adults + $amount_children + $amount_guides;
                $amount = (int) ($validated['adult'] ?? 0) + (int) ($validated['child'] ?? 0) + (int) ($validated['guide'] ?? 0);
                $booking = null; // Booking::where('id', $validated['booking_id'])->where('status_payment_id', 1)->first();
                if (!$booking) {
                    // 1. Create or find customer
                    $customer = Customer::firstOrCreate([
                        'email_address' => $validated['lead_email'],
                    ], [
                        'full_name' => $validated['lead_person'],
                        'contact_number' => $validated['lead_mobile'],
                    ]);
                    $vatMultiplier = LookupItem::where('lookup_id', 1)->where('is_default', true)->first()->multiplier;
                    // 2. Create booking
                    $booking = Booking::create([
                        'user_id' => auth()->user()->id,
                        'session_id' => null,
                        'lead_name' => $customer->full_name,
                        'type_booking_id' => 1,
                        'status_payment_id' => 1,
                        'is_sms' => 0,
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
                //TODO add modal to capture and update inventory overrides
                if (false) { // $validated['override_timeslots']
                    InventoryOverride::create([
                        'inventory_id'     => $inventory->id,
                        'date'           => $validated['overide_date'],
                        'time'           => $validated['overide_time'],
                        'capacity' => $validated['overide_capacity'],
                        'price_adult' => $validated['overide_adult'],
                        'price_child' => $validated['overide_child'],
                        'price_guide' => $validated['overide_guide'],
                        'note' => $validated['override_reason'] ?? '',
                        'created_by_id'     => $validated['user_id'],
                        'updated_by_id'     => $validated['user_id'],
                        'team_id'        => auth()->user()->team_id,
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
                    'inventory_override_id' => null, // $validated['override_timeslots'] ? $inventoryOverride->id : null,
                    'team_id'        => auth()->user()->team_id,
                ]);
                // TODO add logic for creating and validating a voucher code
                $bookingFee = BookingFee::create([
                    'booking_item_id'     => $booking_item->id,
                    'booking_detail_id' => $bookingDetail->id,
                    'tot_adults'     => (int) ($validated['adult'] ?? 0),
                    'tot_children'   => (int) ($validated['child'] ?? 0),
                    'tot_guides'     => (int) ($validated['guide'] ?? 0),
                    'is_voucher'     => $validated['redeem_voucher'] ? 1 : 0,
                    'voucher_code'   => $validated['redeem_voucher'] ?? '',
                    'invoice_note'   => $validated['invoice_note'] ?? '',
                    'is_email_reminder' => $validated['email_reminder'] ? 1 : 0,
                    'booking_note'   => $validated['booking_note'] ?? '',
                    'tot_amount'     => $amount,
                    'team_id'        => auth()->user()->team_id,
                ]);

                $template = InvoiceTemplate::where('is_default', true)->firstOrFail();
                $invoice = Invoice::create([
                    'booking_id'     => $booking->id,
                    'gift_voucher_id'    => null,
                    'invoice_date'   => $validated['booking_date'],
                    'due_date'       => $validated['booking_date'],
                    'subtotal'       => $amount,
                    'vat'            => $amount * 0.15,
                    'total'          => $amount * 1.15,
                    'status'         => 'draft',
                    'invoice_template_id'    => $template->id,
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
                $data = $renderService->invoice($booking, $customer);
                $invoicePdf = Pdf::loadView('invoices.invoice', $data);
                // Optional: tweak DOMPDF options
                $invoicePdf->setPaper('A4', 'portrait');


                $invoiceFilename = 'invoices/' . $data['invoice']['number'] . '.pdf';
                Storage::disk('public')->put($invoiceFilename, $invoicePdf->output());
                $content = "
                        <p>Hello {$customer->full_name},</p>
                        <p>Your booking has been paid.</p>
                        <p>Thank you for choosing Canopy Tours.</p>
                        <p>Your booking reference is {$booking->reference}.</p>
                        <p>Please Contact us if you have any questions.</p>";
                // send mail
                // 🔹 log in emails table
                Email::create([
                    'sender'         => config('mail.from.address'),
                    'receiver'       => $validated['lead_email'],
                    'subject'        => 'Invoice ' . $data['invoice']['number'],
                    'body'           => strip_tags($content), // or a summary
                    'attachment_path' => $invoiceFilename,
                    'sent'           => true,
                    'sent_at'        => now(),
                    'user_id'        => auth()->user()->id,
                    'auditable_type' => 'App\Models\Booking',
                    'auditable_id'   => $booking->id,
                    'team_id'        => auth()->user()->team_id,
                ]);
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
                $voucher  = BookingVoucher::with('items')
                    ->where('service_id', 1)
                    ->where('season_id', 1)
                    ->first();
                //dd($voucher);
                $items = $voucher->items;

                // Build replacements for placeholders
                $replacements = [
                    '{{reference}}' => $booking->reference ?? 'REF-000000',
                    '{{provider}}' => $bookingVoucher->provider_name ?? 'Canopy Tours',
                    '{{service}}' => $bookingVoucher->service_name ?? 'Service Test',
                    '{{start_time_and_date}}' => $bookingVoucher->start_time_and_date ?? '12:00 12 Jan 2025',
                    '{{start_location}}' => $bookingVoucher->start_location ?? 'Start Location',
                    '{{end_location}}' => $bookingVoucher->end_location ?? 'End Location',
                    '{{lead_name}}' => $booking->lead_name ?? 'Test Lead',
                    '{{lead_email}}' => $booking->lead_email ?? 'test@email.com',
                    '{{number_in_party}}' => $booking->number_in_party ?? '8',
                    '{{supplier_email}}' => $bookingVoucher->supplier_email ?? 'test@email.com',
                    '{{supplier_landline}}' => $bookingVoucher->supplier_landline ?? '123456789',
                    '{{supplier_mobile}}' => $bookingVoucher->supplier_mobile ?? '123456789',
                ];

                // Perform replacements in the descriptions before sending to view
                $items->transform(function ($item) use ($replacements) {
                    $item->description = strtr($item->description, $replacements);
                    return $item;
                });

                $random = rand(1, 100);
                $voucherPdf = Pdf::loadView('emails.booking_voucher', compact('items'));
                $voucherFilename  = "vouchers/voucher-{$data['invoice']['number']}.pdf";
                Storage::disk('public')->put($voucherFilename, $voucherPdf->output());

                Email::create([
                    'sender'         => config('mail.from.address'),
                    'receiver'       => $customer->email_address,
                    'subject'        => 'Booking Detail for invoice' . $data['invoice']['number'],
                    'body'           => strip_tags($content), // or a summary
                    'attachment_path' => $voucherFilename,
                    'sent'           => true,
                    'sent_at'        => now(),
                    'user_id'        => null,
                    'auditable_type' => 'App\Models\BookingVoucher',
                    'auditable_id'   => $voucher->id,
                    'team_id'        => $voucher->team_id,
                ]);
                File::create([
                    'team_id'      => $voucher->team_id,
                    'table_name'   => 'BookingVoucher',
                    'table_row_id' => $voucher->id,
                    'file_name'    =>  "voucher-{$voucher->id}.pdf",
                    'file_type'    => "application/pdf",
                    'type_file_id' => 6, // optional enum/type
                    'path'         => $voucherFilename,
                    'booking_id'   => $booking->id,
                ]);
                $attachments = [
                    [
                        'data' => $invoicePdf->output(),
                        'name' => "invoice-{$data['invoice']['number']}.pdf",
                        'mime' => 'application/pdf',
                    ],
                    [
                        'data' => $voucherPdf->output(),
                        'name' => "voucher-{$voucher->id}.pdf",
                        'mime' => 'application/pdf',
                    ],
                ];
                // //send mail
                Mail::to($customer->email_address)->send(new BookingMail($data, $attachments));


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
            return Inertia::location(route('admin.booking-payments.create', $booking->id));
            // return Inertia::location(route('admin.booking_payments.create') . '?booking_id=' . $booking->id);
            // return Inertia::location(route('admin.booking_payments.edit', $booking->id));
            // return redirect()->route('admin.booking_payments.edit', ['booking' => $booking->id]);
        } catch (\Throwable $e) {
            // Log the full error for debugging
            Log::error('Booking failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);
            return back()->with([
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ]);
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
            'booking_staff', // :id,booking_id,user_id
            'booking_staff.user', // :id,name,email,contact_number
        ])->where('booking_id', $booking->id)->first();

        $users = User::select('name', 'id')->get();
        $today = now()->toDateString();
        $status_bookings = TypeBooking::select('name', 'id')->get();
        $wholesalers = Wholesaler::select('id', 'name')->get();
        //$services = Service::select('id', 'name')->get();
        $services = Service::select('id', 'name', 'allow_internal_capacity_override', 'duration', 'capacity')
            ->with('seasons:id,name,start_date,end_date,service_id,rake_rate_adult,rake_rate_child,rake_rate_guide,allow_internal_price_override,status', 'seasons.inventory', 'seasons.inventory.inventory_time_slots')
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
        $lookups = LookupItem::where('is_default', true)->get();

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

    public function update(UpdateBookingRequest $request, Booking $booking)
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

        $manifest = $bookingItem->paxByBookingItem($bookingItem->id);
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
        $lookups = LookupItem::where('is_default', true)->get();

        return Inertia::render('Admin/Bookings/Add', ['booking' => $booking, 'manifest' => $manifest, 'bookingItem' => $bookingItem, 'staff' => $staff, 'lookups' => $lookups]);
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

    public function show(Booking $booking)
    {
        abort_if(Gate::denies('booking_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $booking->load('user', 'status_booking', 'service', 'season', 'inventory', 'updated_by', 'customers', 'team');

        return view('admin.bookings.show', compact('booking'));
    }

    public function destroy(Booking $booking)
    {
        // abort_if(Gate::denies('booking_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $booking_items = $booking->booking_items;
        foreach ($booking_items as $item) {
            $item->delete();
        }

        $booking->delete();

        return back();
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
        $bookingData = DB::table('bookings as b')
            ->leftJoin('booking_items as items', 'items.booking_id', '=', 'b.id')
            ->leftJoin('booking_inventories as bi', 'bi.booking_item_id', '=', 'items.id')
            ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
            ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
            ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
            ->leftJoin('inventory_overrides as io', 'io.id', '=', 'bi.inventory_override_id')
            ->where('items.inventory_id', $activityId)
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

        // allowed days from inventory


        // build schedule (still extendable with promo/closeout)
        $schedule = app(\App\Services\Calendar\ServiceCalendarBuilder::class)
            ->build($inventory, $season, $service, $bookingData, $startDate, $endDate, $totalRequested, $startTime);

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
            $bookingData = DB::table('bookings as b')
                ->leftJoin('booking_inventories as bi', 'bi.booking_id', '=', 'b.id')
                ->leftJoin('inventory_type_days as itd', 'itd.id', '=', 'bi.inventory_type_day_id')
                ->leftJoin('type_days as td', 'td.id', '=', 'itd.type_day_id')
                ->leftJoin('inventory_time_slots as its', 'its.id', '=', 'bi.inventory_time_slot_id')
                ->leftJoin('inventory_overrides as io', 'io.id', '=', 'bi.inventory_override_id')
                ->where('b.inventory_id', $activityId)
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
                logger()->info('promo ' . $promo->id);


                if ($promo) {
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
                    $totalPax  = $adultCount + $childCount;
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
                                    $rates['adult'] = round(max(0, $rates['adult'] - $fv), 2);
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
}
