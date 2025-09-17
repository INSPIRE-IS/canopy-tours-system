<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingQuestionnaire;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\TypeConfirmation;
use App\Models\TypeGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CheckinsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $booking_id, $customer_id = null)
    {
        //dd($customer_id);
        $user = auth()->user();
        $customerId = $customer_id; // your passed in customer ID
        $booking = Booking::findOrFail($booking_id);
        $booking->load([
            'booking_payments',
        ]);
        // if ($customerId == null) {
        //     $customerId = $booking->customer_id;
        // }

        $bookingItem = BookingItem::with([
            'service',
            'season',
            'inventory',
            'customers',
            'customer_manifests.customer.booking_questionnaire',
            'booking_infos',
            'customers.booking_questionnaire',
            'booking_details',
            'booking_fees',
            'booking_inventory',
            'booking_staff',
            'booking_staff.user',
        ])
            ->where('booking_id', $booking->id)
            ->when($customerId ?? null, function ($query) use ($customerId) {
                $query->whereHas('customers', function ($q) use ($customerId) {
                    $q->where('customers.id', $customerId);
                });
            })
            ->orderBy('booking_items.id') // or some other column for order
            ->skip(0)       // skip the first
            ->first();      // get the second



        // $booking->load([
        //     'booking_items',
        //     'booking_items.service',
        //     'booking_items.season',
        //     'booking_items.inventory',
        //     //'customers',  :id,name,email,contact_number
        //     //'customer_manifests',  :id,position,notes,is_indemnity,customer_id,booking_id\
        //     'booking_items.booking_infos', // :id,booking_id,type_location_id,location,time,created_at,updated_at,deleted_at
        //     //'customers.booking_questionnaire',  :id,booking_id,customer_id,guide_id,safety_guide_id,type_group_id,fullname,email,signature,birth_date,type_referral,country_id,province_id,is_medical,medical_detail,is_fit,is_agree
        //     'booking_items.booking_details', //:id,booking_id ,start_time,end_time
        //     'booking_items.booking_fees', //:id,booking_id,tot_amount
        //     'booking_payments', // :id,booking_id,amount
        //     'booking_items.booking_inventory', // :id,booking_id,inventory_id
        //     'booking_items.booking_staff', // :id,booking_id,user_id
        //     'booking_items.booking_staff.user', // :id,name,email,contact_number
        //     // Filter customers by ID
        //     'booking_items.customers' => function ($q) use ($customerId) {
        //         $q->where('customers.id', $customerId);
        //     },

        //     // Filter customer manifests by customer ID
        //     'booking_items.customer_manifests' => function ($q) use ($customerId) {
        //         $q->where('customer_id', $customerId);
        //     },

        //     // Filter nested relation (booking_questionnaire) from customers
        //     'booking_items.customers.booking_questionnaire' => function ($q) use ($customerId) {
        //         $q->where('customer_id', $customerId);
        //     },


        // ])
        //     // ->select([
        //     //     'id',
        //     //     'lead_name',
        //     //     'user_id',
        //     //     'status_booking_id',
        //     //     'due_date',
        //     //     'tot_paid',
        //     //     'grand_tot_due',
        //     //     'service_id',
        //     //     'season_id',
        //     //     'inventory_id',
        //     //     'created_at'
        //     // ])
        //     //->whereDate('due_date', '2025-01-01')
        //     ->get();

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
        $typeGroup = TypeGroup::select('id', 'name')->get();
        $typeConfirmation = TypeConfirmation::select('id', 'name')->get();
        //dd($booking);

        return Inertia::render('Admin/Checkins/Index', ['booking' => $booking, 'booking_item' => $bookingItem, 'staff' => $staff, 'typeGroup' => $typeGroup, 'typeConfirmation' => $typeConfirmation]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $bookingId = request()->route('bookingId');
        return Inertia::render('Questionnaire', ['bookingId' => $bookingId]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'service_id' => 'required|integer', //   "service_id" => 1
            'season_id' => 'required|integer', //   "season_id" => 1
            'booking_id' => 'required|integer', //   "booking_id" => 4
            'customer_id' => 'required|integer', //     "customer_id" => 9
            //'team_id' => 'required|integer', //     "customer_id" => 9
            'is_lead' => 'nullable|integer', //     "customer_id" => 9
            'first_name' => 'required|string', //     "first_name" => "Customer9"
            'email_address' => 'required|email', //     "email_address" => "kylamyf@mailinator.com"
            'contact_number' => 'required|string', //     "contact_number" => "446"   
            'identity_number' => 'nullable|string', //   "identity_number" => "271"
            'nationality_id' => 'nullable|integer', //   "nationality_id" => null
            'continent_id' => 'required|integer', //   "continent_id" => "5"
            'country_id' => 'required|integer', //   "country_id" => "44"
            'province_id' => 'required|integer', //   "province_id" => "714"
            'date_of_birth' => 'nullable|date', //    "date_of_birth" => null
            'pully_number' => 'required|string', //   "pully_number" => "320"
            'gender_id' => 'required|integer', //   "gender_id" => "Sint aliqua Ipsa s"
            //'type_group_id' => 'required|integer', //  "type_group_id" => "4"
            'recieve_invite' => 'required|integer', //    "recieve_invite" => "2"
            'medical_detail' => 'required|string', //    "medical_detail" => "Commodi facere qui t"
            'helmet_size' => 'required|string', //   "helmet_size" => "Quidem quisquam ulla"
            'is_fit' => 'required|integer', //   "is_fit" => "2"
            'safety_guide_id' => 'required|integer', //   "safety_guide_id" => "6"
            'lead_guide_id' => 'required|integer', //   "lead_guide_id" => "6"
            'is_minor' => 'required|in:1,2,3',
            'children' => 'array',
            'children.*.child_name' => 'required_with:children|string|max:255',
            'children.*.child_age' => 'required_with:children|integer|min:0',
            'full_name' => 'required|string', //   "full_name" => "Brendan Sweet"
            'birth_date' => 'required|date', //   "birth_date" => "1997-02-16"
        ]);

        // "children" => array:2 [▼
        //     0 => array:2 [▼
        //       "child_name" => "Plato Bryan"
        //       "child_age" => "Enim do do perspicia"
        //     ]
        //     1 => array:2 [▼
        //       "child_name" => "Quinlan Cantrell"
        //       "child_age" => "Illum ad ut possimu"
        //     ]
        //   ]
        try {
            $customer = DB::transaction(function () use ($validated) {
                $customer = Customer::firstOrCreate([
                    'id' => $validated['customer_id'],
                ], [
                    'full_name' => $validated['full_name'],
                    'email_address' => $validated['email_address'],
                    'contact_number' => $validated['contact_number'],
                    'nationality_id' => $validated['nationality_id'],
                    'age' => $validated['birth_date'],
                ]);

                if (false) {
                    // Prepare pivot data
                    $pivotData = [];
                    $pivotData[$customer->id] = [
                        'team_id' => null,   // TODO: get team from the page or session
                        'is_lead' => 1,     // First customer is lead, rest are not
                    ];
                    // Sync with pivot data
                    $booking->customers()->sync($pivotData);
                }

                if ($validated['is_minor'] == 1 && $validated['children'] != null) {
                    $validated['children'] = collect($validated['children']);
                    $validated['children']->each(function ($child) use ($customer) {
                        $child['parent_id'] = $customer->id;
                        $child['full_name'] = $child['child_name'];
                        $child['age'] = $child['child_age'];
                        Customer::create($child);
                    });
                }


                $bookingQuestionnaire = BookingQuestionnaire::create([
                    'booking_id' => $validated['booking_id'],
                    'customer_id' => $validated['customer_id'],
                    'guide_id' => $validated['lead_guide_id'],
                    'safety_guide_id' => $validated['safety_guide_id'],
                    'type_group_id' => 1, // $validated['type_group_id'],
                    'signature' => $validated['full_name'],
                    'birth_date' => $validated['birth_date'],
                    'type_referral' => 1, // $validated['type_referral'],
                    'country_id' => $validated['country_id'],
                    'province_id' => $validated['province_id'],
                    'is_medical' => $validated['medical_detail'] != "" ? 1 : 0,
                    'medical_detail' => $validated['medical_detail'],
                    'is_fit' => $validated['is_fit'],
                    'is_minor' => $validated['is_minor'],
                    'is_agree' => true,
                    'helmet_size' => $validated['helmet_size'],
                    'children' => $validated['children'],
                    'gender_id' => $validated['gender_id'],
                    'recieve_invite' => $validated['recieve_invite'],
                    'pully_number' => $validated['pully_number'],
                ]);

                CustomerManifest::create([
                    'position' => '0',
                    'notes' => $validated['medical_detail'],
                    'is_indemnity' => 1,
                    'customer_id' => $validated['customer_id'],
                    'booking_id' => $validated['booking_id'],
                ]);
                return $customer;
            });

            // 4. Save to booking_inventories or other linked tables
            // return response()->json([
            //     'success' => true,
            //     'booking_id' => $booking->id,
            //     'redirect_url' => route('front.payment.review', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
            // ]);
            //return redirect()->back()->with('booking_id', $booking->id);
            return redirect()->route('admin.bookings.index');
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

        return redirect()->route('admin.bookings.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        $bookingId = request()->route('bookingId');
        return Inertia::render('Questionnaire', ['bookingId' => $bookingId]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        //
    }
}
