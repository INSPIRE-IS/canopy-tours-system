<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingQuestionnaire;
use Illuminate\Http\Request;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\File;
use App\Models\Quesionnaire;
use App\Models\State;
use App\Models\TypeConfirmation;
use App\Models\TypeGroup;
use App\Models\TypeReferral;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BookingQuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Questionnaire-Welcome');
    }

    public function reference($reference)
    {
        // dd($reference);
        $booking = Booking::where('reference', $reference)->first();
        //dd($booking);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'error' => 'Booking not found',
            ], 200);
        }
        return response()->json([
            'success' => true,
            'booking' => $booking,
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        //dd($request->all());
        $user = auth()->user();
        $customerId = $request->customer ?? null; // your passed in customer ID
        $booking = Booking::where('reference', $request->reference)->first();
        if (!$booking) {
            return response()->json([
                'success' => false,
                'error' => 'Booking not found',
            ], 200);
        }
        //$booking = Booking::findOrFail($booking->id);

        $booking->load(['booking_payments']);
        $bookingItem = BookingItem::where('booking_id', $booking->id)->first();
        $bookingItem->load([
            'service',
            'season',
            'inventory',
            //'customers',  :id,name,email,contact_number
            //'customer_manifests',  :id,position,notes,is_indemnity,customer_id,booking_id\
            'booking_infos', // :id,booking_id,type_location_id,location,time,created_at,updated_at,deleted_at
            //'customers.booking_questionnaire',  :id,booking_id,customer_id,guide_id,safety_guide_id,type_group_id,fullname,email,signature,birth_date,type_referral,country_id,province_id,is_medical,medical_detail,is_fit,is_agree
            'booking_details', //:id,booking_id ,start_time,end_time
            'booking_fees', //:id,booking_id,tot_amount
            // :id,booking_id,amount
            'booking_inventory', // :id,booking_id,inventory_id
            'booking_staff', // :id,booking_id,user_id
            'booking_staff.user', // :id,name,email,contact_number
            // Filter customers by ID
            'customers' => function ($q) use ($customerId) {
                $q->where('customers.id', $customerId);
            },
            // Filter customer manifests by customer ID
            'customer_manifests' => function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            },
            // Filter nested relation (booking_questionnaire) from customers
            'customers.booking_questionnaire' => function ($q) use ($customerId) {
                $q->where('customer_id', $customerId);
            },
        ])
            // ->select([
            //     'id',
            //     'lead_name',
            //     'user_id',
            //     'status_booking_id',
            //     'due_date',
            //     'tot_paid',
            //     'grand_tot_due',
            //     'service_id',
            //     'season_id',
            //     'inventory_id',
            //     'created_at'
            // ])
            //->whereDate('due_date', '2025-01-01')
            ->get();

        $leadGuides = User::with('roles:id,name')
            ->whereHas('roles', function ($query) {
                $query->whereIn('id', [5]);
            })
            ->when($booking->team_id, function ($query, $teamId) {
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
        $safetyGuides = User::with('roles:id,name')
            ->whereHas('roles', function ($query) {
                $query->whereIn('id', [6]);
            })
            ->when($booking->team_id, function ($query, $teamId) {
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
        $typeConfirmation = TypeConfirmation::whereIn('id', [1, 2])->select('id', 'name')->get();
        $typeReferral = TypeReferral::select('id', 'name')->get();


        //$bookingId = request()->route('bookingId');
        //dd($bookingId);
        return Inertia::render('Questionnaire', [
            'booking' => $booking,
            'bookingItem' => $bookingItem,
            'leadGuides' => $leadGuides,
            'safetyGuides' => $safetyGuides,
            'typeGroup' => $typeGroup,
            '
            typeReferral' => $typeReferral,
            'typeConfirmation' => $typeConfirmation
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd($request->all());

        $validated = $request->validate([
            'booking_id' => 'required|integer', //   "booking_id" => 4
            'lead_guide_id' => 'required|integer', //   "lead_guide_id" => "6"
            'safety_guide_id' => 'required|integer', //   "safety_guide_id" => "6"
            'type_group_id' => 'required|integer', //  "type_group_id" => "4"
            'first_name' => 'required|string', //     "first_name" => "Customer9"
            'date_of_birth' => 'nullable|date', //    "date_of_birth" => null
            'referral_id' => 'nullable|integer', //    "date_of_birth" => null
            'continent_id' => 'required|integer', //   "continent_id" => "5"
            'country_id' => 'required|integer', //   "country_id" => "44"
            'province_id' => 'required|integer', //   "province_id" => "714"
            'medical_id' => 'nullable|string', //   "medical_id" => "Commodi facere qui t"
            'medical_detail' => 'nullable|string', //    "medical_detail" => "Commodi facere qui t"
            'is_fit' => 'required|integer', //   "is_fit" => "2"
            'is_agree' => 'required|integer', //   "is_agree" => "2"
            'email_address' => 'required|email', //     "email_address" => "kylamyf@mailinator.com"
            'signature' => 'required|string', //    "medical_detail" => "Commodi facere qui t"
        ]);

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
                    'team_id' => $validated['team_id'],
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


                // Optionally save the signature as a file
                $image = $validated['signature'];
                $image = str_replace('data:image/png;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $filename = 'signature_' . time() . '.png';
                \Storage::disk('public')->put("signatures/{$filename}", base64_decode($image));

                File::create([
                    'team_id' => $validated['team_id'],
                    'table_name' => 'bookings',
                    'table_row_id' => $validated['booking_id'],
                    'file_name' => $filename,
                    'file_type' => 'image/png',
                    'type_file_id' => 2, // signature
                    'path' => "signatures/{$filename}",
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
    public function show(BookingQuestionnaire $bookingQuestionnaire)
    {
        $bookingId = request()->route('bookingId');
        return Inertia::render('Questionnaire', ['bookingId' => $bookingId]);
    }

    public function getContinents()
    {
        return Continent::orderBy('name')->where('name', '!=', ' ')->wherenotnull('name')->get();
    }

    public function getCountries($continentId)
    {
        return Country::select('id', 'nationality as name')->where('continent_id', $continentId)->where('name', '!=', ' ')->wherenotnull('name')->orderBy('name')->get();
    }

    public function getStates($countryId)
    {
        return State::where('country_id', $countryId)->where('name', '!=', ' ')->wherenotnull('name')->orderBy('name')->get();
    }
}
