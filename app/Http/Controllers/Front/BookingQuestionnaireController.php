<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingQuestionnaire;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\File;
use App\Models\Quesionnaire;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAnswer;
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

        $booking->load(['booking_payments', 'team']);
        $bookingItem = BookingItem::where('booking_id', $booking->id)->first();
        $bookingItem->load([
            'service',
            'season',
            'inventory',
            //'customers',  :id,name,email,contact_number
            //'customer_manifests',  :id,position,notes,is_indemnity,customer_id,booking_id\
            'booking_infos', // :id,booking_id,type_location_id,location,time,created_at,updated_at,deleted_at
            //'customers.booking_questionnaire',  :id,booking_id,customer_id,guide_id,safety_guide_id,type_group_id,fullname,email,signature,birth_date,type_referral,country_id,province_id,is_medical,medical_detail,is_fit,is_agree
            'booking_detail', //:id,booking_id ,start_time,end_time
            'booking_fee', //:id,booking_id,tot_amount
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
        $team = Team::select('id', 'name', 'description', 'color')->with('file_image', 'file_logo')->find($bookingItem->team_id);
        $questionnaire = Questionnaire::with('fields')
            ->where('service_id', $bookingItem->service_id)
            ->where('season_id', $bookingItem->season_id)
            ->first();

        $questionnaireFlat = $questionnaire->fields->map(function ($field) {
            if ($field->pivot->visable == 1) {
                // if ($field->type_input_id == 18) {
                //     $field->options = TypeConfirmation::all()->pluck('name', 'id')->toArray();
                // }
                return [
                    "id"                   => $field->id,
                    "question"             => $field->question,
                    "description"          => $field->description,
                    "slug"                 => $field->slug,
                    "options"              => $field->options,
                    "conditional_logic"    => $field->conditional_logic,
                    "type_input_id"        => $field->type_input_id,
                    "type_data_id"         => $field->type_data_id,
                    "required"             => $field->pivot->required,
                    "visable"              => $field->pivot->visable,
                    "sort_order"           => $field->pivot->sort_order,
                    "questionnaire_id"     => $field->pivot->questionnaire_id,
                    "questionnaire_field_id" => $field->pivot->questionnaire_field_id,
                ];
            }
        })->filter();
        //dd($questionnaireFlat);

        //$bookingId = request()->route('bookingId');
        //dd($typeReferral);
        return Inertia::render('Questionnaire', [
            'booking' => $booking,
            'bookingItem' => $bookingItem,
            'leadGuides' => $leadGuides,
            'safetyGuides' => $safetyGuides,
            'typeGroup' => $typeGroup,
            'typeReferral' => $typeReferral,
            'typeConfirmation' => $typeConfirmation,
            'questionnaire' => $questionnaireFlat,
            'team' => $team,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());

        $validated = $request->validate([
            'booking_id' => 'required|integer', //   "booking_id" => 4
            "booking_item_id" => 'required|integer',
            "customer_id"  => 'nullable|integer',
            'lead_guide_id' => 'nullable|integer', //   "lead_guide_id" => "6"
            'safety_guide_id' => 'nullable|integer', //   "safety_guide_id" => "6"
            'type_group_id' => 'nullable|integer', //  "type_group_id" => "4"
            'full_name' => 'string', //     "first_name" => "Customer9"
            'age' => 'nullable|integer', //    "date_of_birth" => null
            'referral_id' => 'nullable|integer', //    "date_of_birth" => null
            'continent_id' => 'nullable|integer', //   "continent_id" => "5"
            'country_id' => 'nullable|integer', //   "country_id" => "44"
            'province_id' => 'nullable|integer', //   "province_id" => "714"
            'medical_id' => 'nullable|string', //   "medical_id" => "Commodi facere qui t"
            'medical_detail' => 'nullable|string', //    "medical_detail" => "Commodi facere qui t"
            'is_fit' => 'nullable|integer', //   "is_fit" => "2"
            'is_agree' => 'nullable|integer', //   "is_agree" => "2"
            'email_address' => 'nullable|email', //     "email_address" => "kylamyf@mailinator.com"
            'signature' => 'nullable|string', //    "medical_detail" => "Commodi facere qui t"
            'is_minor' => 'nullable|integer', //   "is_minor" => "2"
            'children' => 'nullable|array',
            'gender_id' => 'nullable|integer', //   "gender_id" => "Sint aliqua Ipsa s"
            'child_name' => 'nullable|string', //   "child_name" => "Plato Bryan"
            'child_age' => 'nullable|integer', //   "child_age" => "Enim do do perspicia"
            'helmet_size' => 'nullable|string', //   "helmet_size" => "Quidem quisquam ulla"
            'recieve_invite' => 'nullable|integer', //    "recieve_invite" => "2"
            'pully_number' => 'nullable|string', //   "pully_number" => "320"
        ]);



        try {
            $booking = DB::transaction(function () use ($validated) {
                $booking = Booking::find($validated['booking_id']);
                $bookingItem = BookingItem::find($validated['booking_item_id']);

                $customer = Customer::updateOrCreate([
                    'id' => $validated['customer_id'] ?? null,
                ], [
                    'full_name' => $validated['full_name'],
                    'email_address' => $validated['email_address'],
                    //'contact_number' => $validated['contact_number'],
                    //'nationality_id' => $validated['nationality_id'],
                    'age' => $validated['age'],
                ]);
                //dd('here');
                if (true) {
                    // Prepare pivot data
                    $pivotData = [];
                    $pivotData[$customer->id] = [
                        'team_id' => $bookingItem->team_id,   // TODO: get team from the page or session
                        'is_lead' => 0, //$customer->is_lead,     // First customer is lead, rest are not
                    ];
                    // Sync with pivot data
                    $bookingItem->customers()->sync($pivotData);
                }
                $is_minor = $validated['is_minor'] ?? null;
                $children = $validated['children'] ?? null;
                if ($is_minor == 1 && $children != null) {

                    $validated['children'] = collect($validated['children']);
                    $validated['children']->each(function ($child) use ($customer) {
                        $child['parent_id'] = $customer->id;
                        $child['full_name'] = $child['child_name'];
                        $child['age'] = $child['child_age'];
                        Customer::create($child);
                    });
                }

                // $questionaire_answers = QuestionnaireAnswer::create([
                //     'questionnaire_id' => $validated['questionnaire_id'],
                //     'questionnaire_field_id' => $validated['questionnaire_field_id'],
                //     'customer_id' => $validated['customer_id'],
                //     'value' => $validated['value'],
                //     'team_id' => $validated['team_id'],
                // ]);


                $bookingQuestionnaire = BookingQuestionnaire::create([
                    'booking_item_id' => $bookingItem->id,
                    'team_id' => $bookingItem->team_id,
                    'customer_id' => $customer->id,
                    'guide_id' => $validated['lead_guide_id'] ?? null,
                    'safety_guide_id' => $validated['safety_guide_id'] ?? null,
                    'type_group_id' => $validated['type_group_id'] ?? null,
                    'signature' => $validated['full_name'] ?? null,
                    'age' => $validated['age'] ?? null,
                    'type_referral' => $validated['referral_id'] ?? null,
                    'country_id' => $validated['country_id'] ?? null,
                    'province_id' => $validated['province_id'] ?? null,
                    'is_medical' => $validated['medical_detail'] != "" ? 1 : 0,
                    'medical_detail' => $validated['medical_detail'] ?? null,
                    'is_fit' => $validated['is_fit'] ?? null,
                    'is_minor' => $validated['is_minor'] ?? null,
                    'is_agree' => $validated['is_agree'] ?? null,
                    'helmet_size' => $validated['helmet_size'] ?? null,
                    'children' => $validated['children'] ?? null,
                    'gender_id' => $validated['gender_id'] ?? null,
                    'recieve_invite' => $validated['recieve_invite'] ?? null,
                    'pully_number' => $validated['pully_number'] ?? null,
                ]);

                CustomerManifest::create([
                    'position' => '0',
                    'notes' => $validated['medical_detail'] ?? null,
                    'is_indemnity' => 1,
                    'customer_id' => $customer->id,
                    'booking_item_id' => $bookingItem->id,
                    'type'           => 'child',
                    'team_id'        => $bookingItem->team_id,

                ]);


                // Optionally save the signature as a file
                $image = $validated['signature'] ?? null;
                $image = str_replace('data:image/png;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $filename = 'signature_' . time() . '.png';
                \Storage::disk('public')->put("signatures/{$filename}", base64_decode($image));

                File::create([
                    'team_id' => $bookingItem->team_id,
                    'table_name' => 'booking_items',
                    'table_row_id' => $bookingItem->id,
                    'file_name' => $filename,
                    'file_type' => 'image/png',
                    'type_file_id' => 2, // signature
                    'path' => "signatures/{$filename}",
                ]);

                return $booking;
            });

            // 4. Save to booking_inventories or other linked tables
            // return response()->json([
            //     'success' => true,
            //     'booking_id' => $booking->id,
            //     'redirect_url' => route('front.payment.review', ['booking' => $booking->id]) //  route('payfast.checkout', ['booking' => $booking->id])
            // ]);
            return redirect()->back()->with('booking_id', $booking->id);
            // dd('here');
            return redirect()->route('/');
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
        return back()->with([
            'error' => 'Something went wrong while saving your booking. Please try again.',
        ]);
        return redirect()->route('/');
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
