<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerManifestRequest;
use App\Mail\CheckInMail;
use App\Models\Alert;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingQuestionnaire;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Continent;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerManifest;
use App\Models\Email;
use App\Models\File;
use App\Models\Quesionnaire;
use App\Models\Question;
use App\Models\QuestionAnswer;
use App\Models\Questionnaire;
use App\Models\QuestionnaireAnswer;
use App\Models\QuestionSite;
use App\Models\Service;
use App\Models\Season;
use App\Models\State;
use App\Models\TypeConfirmation;
use App\Models\TypeGroup;
use App\Models\TypeReferral;
use App\Models\TypeFile;
use App\Models\User;
use App\Services\QuestionnaireSeeder;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Mail;
use Storage;

class BookingQuestionnaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // TODO: This index is currently public; consider auth-guarding it to avoid exposing bookings.
        $bookingDate = $request->query('booking_date');
        try {
            $bookingDate = $bookingDate ? Carbon::parse($bookingDate)->toDateString() : now()->toDateString();
        } catch (\Throwable $e) {
            $bookingDate = now()->toDateString();
        }

        $booking_items = BookingItem::with([
            'service',
            'booking.files',
            'customers',
            'customer_manifests',
            'booking_detail',
            'booking_fee',
            'booking_inventory',
        ])
            ->whereHas('booking_inventory', function ($query) use ($bookingDate) {
                $query->whereDate('date', $bookingDate);
            })
            ->get();
        //dd($booking_items);

        $formatted = $booking_items->map(function ($booking_item) {

            try {
                $price = $booking_item->booking_fee->tot_amount;
                $paid = $booking_item->booking_fee->tot_paid;
                $due = $price - $paid;


                $customers = $booking_item->paxWithCustomers();
                //dd($booking_item);
                $info = [
                    'id' => $booking_item->booking->id,
                    'booking_item_id' => $booking_item->id,
                    'service_id' => $booking_item->service_id,
                    'service_name' => $booking_item->service->name,
                    'reference' => $booking_item->booking->reference,
                    'lead_name' => $booking_item->booking->lead_name . ' - ' . $booking_item->service->name,
                    'lead_id' => $booking_item->booking->customer_id,
                    'customers' => $customers,
                ];
                //dd($info);
                return $info;
            } catch (\Throwable $e) {
                Log::error('Booking', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'input' => $booking_item,
                ]);
                //dd($booking_item);
                return null;
            }
        });
        //dd($formatted);
        return Inertia::render('Questionnaire-Welcome', [
            'bookings' => $formatted,
        ]);
    }

    public function reference($reference)
    {
        //dd($reference);
        $booking = Booking::with('booking_items.customers', 'booking_items.customer_manifests')->where('reference', $reference)->first();
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

    protected function resolveCheckinExpiry(?BookingItem $bookingItem): ?Carbon
    {
        if (!$bookingItem) {
            return null;
        }

        $bookingItem->loadMissing(['booking_inventory.inventory_time_slot']);
        $bookingInventory = $bookingItem->booking_inventory;
        $bookingDate = $bookingInventory?->getRawOriginal('date') ?? $bookingInventory?->date;
        $startTime = $bookingInventory?->inventory_time_slot?->start_time;

        if (!$bookingDate || !$startTime) {
            return null;
        }

        try {
            $startDateTime = Carbon::parse(trim("{$bookingDate} {$startTime}"), config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }

        return $startDateTime->copy()->addHours(3);
    }

    public function signed(Request $request)
    {
        $validated = $request->validate([
            'booking_item_id' => 'required|integer',
            'reference' => 'required|string',
        ]);

        if (!$validated) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ], 500);
        }

        $bookingItem = BookingItem::with(['booking_inventory.inventory_time_slot'])->find($request->booking_item_id);
        if (!$bookingItem) {
            return Inertia::render('Front/BookingQuestionnaire/Expired', [
                'message' => 'Booking not found. Please contact your booking agent for assistance.',
                'activityDate' => null,
                'activityOver' => false,
            ]);
        }
        $expiresAt = $this->resolveCheckinExpiry($bookingItem);
        if (!$expiresAt) {
            return Inertia::render('Front/BookingQuestionnaire/Expired', [
                'message' => 'Activity start time is not set. Please contact your booking agent to confirm activity start time.',
                'activityDate' => $bookingItem?->booking_inventory?->getRawOriginal('date'),
                'activityOver' => false,
            ]);
        }

        $url = URL::temporarySignedRoute(
            'front.booking-questionnaire.checkin',
            $expiresAt,
            [
                'booking_item_id' => $request->booking_item_id,
                'reference' => $request->reference,
            ]
        );
        return Inertia::location($url);
    }

    public function checkin(Request $request)
    {
        // dd($request->all());
        try {
            $bookingItem = BookingItem::with([
                'booking',
                'service',
                'customers',
                'customer_manifests',
            ])
                ->where('id', $request->booking_item_id)
                ->first();
            //dd($bookingItem);
            //dd($request->all());
            $user = auth()->user();
            $customerId = $request->customer ?? null; // your passed in customer ID

            if (!$bookingItem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Booking not found',
                ], 200);
            }

            $questionnaire = Questionnaire::where('service_id', $bookingItem->service_id)
                ->where('season_id', $bookingItem->season_id)
                ->first();
            if (!$questionnaire && $bookingItem->season_id) {
                $season = Season::find($bookingItem->season_id);
                if ($season) {
                    app(QuestionnaireSeeder::class)->seedForSeason($season);
                }
            }
            $questions = Question::whereHas('question_sites', function ($q) use ($bookingItem) {
                $q->where('service_id', $bookingItem->service_id)
                    ->where('enabled', 1);
            })
                ->with(['question_sites' => function ($q) use ($bookingItem) {
                    $q->where('service_id', $bookingItem->service_id)
                        ->where('enabled', 1)
                        ->orderBy('sort_order');
                }])
                ->where('type', 'indemnity')
                ->get()->map(function ($question) {
                    //dd($question);
                    return collect([
                        "id" => $question->id,
                        "enabled" => $question->question_sites->first()->enabled,
                        "sort_order" => $question->question_sites->first()->sort_order,
                        "service_id" => $question->service_id,
                        "name" => $question->name,
                        "description" => $question->description,
                        "slug" => $question->slug,
                        "options" => $question->options,
                        "conditional_logic" => $question->conditional_logic,
                        "type" => $question->type,
                    ]);
                });

            // $questionMaps = QuestionSite::with('question')
            //     ->where('service_id', $bookingItem->service_id)
            //     ->get()
            //     ->map(function ($question) {
            //         if ($question->question->type != "indemnity") {
            //             return null;
            //         }
            //         return collect([
            //             "id" => $question->id,
            //             "enabled" => $question->enabled,
            //             "sort_order" => $question->sort_order,
            //             "question_id" => $question->question_id,
            //             "service_id" => $question->service_id,
            //             "name" => $question->question->name,
            //             "description" => $question->question->description,
            //             "slug" => $question->question->slug,
            //             "options" => $question->question->options,
            //             "conditional_logic" => $question->question->conditional_logic,
            //             "type" => $question->question->type,
            //         ]);
            //     });



            $answers = QuestionAnswer::where('booking_item_id', $bookingItem->id)->get();

            $formatted = null;

            if ($bookingItem) {
                try {
                    $price = $bookingItem->booking_fee->tot_amount;
                    $paid = $bookingItem->booking_fee->tot_paid;
                    $due = $price - $paid;

                    // $customers = $bookingItem->paxWithCustomers();
                    $customers = $bookingItem->paxWithCustomers();

                    $customers = $customers->map(function ($customer) use ($questions, $answers) {
                        Log::info('customer1', (array) $customer);

                        // Convert to object
                        $real = $customer;

                        $customer = (object) $customer;
                        //$questions = (object) $questions;

                        // Manifest
                        $manifest = CustomerManifest::where('booking_item_id', $customer->booking_item_id)
                            ->where('customer_id', $customer->customer_id)
                            ->first();

                        Log::info('customer2', (array) $customer);
                        // $manifest = $manifest->toArray();
                        // dd($manifest);

                        // Questionnaire
                        $questionnaire = $questions->map(function ($q) use ($answers, $customer) {
                            // dd($q['id']);
                            $existing = $answers->first(function ($a) use ($customer, $q) {
                                return $a->customer_id == $customer->customer_id &&
                                    $a->question_id == $q['id'];
                            });

                            $valueFromCustomer = $customer->{$q['slug']} ?? null;

                            if ($valueFromCustomer !== null) {

                                // update existing
                                if ($existing) {
                                    $existing->answer = $valueFromCustomer;
                                    $existing->save();
                                }
                                // create new answer
                                else {
                                    QuestionAnswer::create([
                                        'booking_item_id' => $customer->booking_item_id,
                                        'customer_id'     => $customer->customer_id,
                                        'question_id'     => $q['id'],
                                        'answer'          => $valueFromCustomer,
                                    ]);
                                }
                            }
                            //dd($q);
                            // $other = $questionMaps->firstWhere('slug', $q->slug);
                            $output =  [
                                'question_id' => $q['id'],
                                'slug' => $q['slug'],
                                'name' => $q['name'],
                                'type' => $q['type'],
                                'options' => $q['options'],
                                'sort_order' => $q['sort_order'],
                                'enabled' => $q['enabled'],
                                'conditional_logic' => $q['conditional_logic'],
                                'description' => $q['description'],
                                'answer' => $existing ? $existing->answer : $valueFromCustomer,
                            ];
                            Log::info('customer4', (array) $output);
                            return $output;
                        });
                        //dd($questionnaire);
                        Log::info('customer4', (array) $customer);
                        // return array_merge(
                        //     $real,
                        //     // $pivot,
                        //     $manifest,
                        //     $questionnaire
                        // );
                        return [
                            'customer' => $customer,
                            'manifest' => $manifest,
                            'questionnaire' => $questionnaire
                        ];
                    });
                    $formatted = collect([
                        'id' => $bookingItem->booking->id,
                        'booking_item_id' => $bookingItem->id,
                        'service_id' => $bookingItem->service_id,
                        'service_name' => $bookingItem->service->name,
                        'reference' => $bookingItem->booking->reference,
                        'lead_name' => $bookingItem->booking->lead_name,
                        'lead_id' => $bookingItem->booking->customer_id,
                        'customers' => $customers,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Booking', [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'input' => $bookingItem,
                    ]);
                }
            }
            // dd($formatted);
            $leadGuides = User::with('roles:id,name')
                ->whereHas('roles', function ($query) {
                    $query->whereIn('id', [5]);
                })
                ->when($bookingItem->booking->team_id, function ($query, $teamId) {
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
                ->when($bookingItem->booking->team_id, function ($query, $teamId) {
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

            $typeGroups = TypeGroup::select('id', 'name')->get();
            $ageGroups = collect(Questionnaire::AGES);
            $typeGender = collect(Questionnaire::GENDERS);
            $documents = collect(Questionnaire::DOCUMENTS);
            $typeConfirmation = TypeConfirmation::whereIn('id', [1, 2])->select('id', 'name')->get();
            $typeReferral = TypeReferral::select('id', 'name')->get();
            $team = Team::select('id', 'name', 'description', 'color')->with('file_image', 'file_logo')->find($bookingItem->team_id);

            //dd($questions);
            //$bookingId = request()->route('bookingId');
            // dd($formatted);
            return Inertia::render('Questionnaire', [
                'booking' => $bookingItem->booking,
                'bookingItem' => $bookingItem,
                'leadGuides' => $leadGuides,
                'safetyGuides' => $safetyGuides,
                'typeGroups' => $typeGroups,
                'ageGroups' => $ageGroups,
                'typeGender' => $typeGender,
                'documents' => $documents,
                'typeReferral' => $typeReferral,
                'typeConfirmation' => $typeConfirmation,
                'questions' => $questions,
                'team' => $team,
                'bookings' => $formatted,
            ]);
        } catch (InvalidSignatureException $e) {
            $bookingItemId = $request->query('booking_item_id');
            $activityDate = null;
            $activityOver = false;

            if ($bookingItemId) {
                $bookingItem = BookingItem::with('booking_inventory')->find($bookingItemId);
                $activityDate = optional($bookingItem?->booking_inventory)->date;
                if ($activityDate) {
                    $activityOver = Carbon::parse($activityDate)->endOfDay()->isPast();
                }
            }

            $message = $activityOver
                ? 'This check-in link has expired and the activity has already passed.'
                : 'This check-in link has expired. Please contact your booking agent for a new link.';

            return Inertia::render('Front/BookingQuestionnaire/Expired', [
                'message' => $message,
                'activityDate' => $activityDate,
                'activityOver' => $activityOver,
            ]);
        }
    }

    public function UpdateCustomerManifest(UpdateCustomerManifestRequest $request)
    {
        dd($request->all());

        $validated = $request->validate([
            'customer_manifests' => 'required|array',
        ]);
        $details = $validated['customer_manifests'];
        $bookingItemForLink = null;
        //dd($details);
        $customerId = null;
        $customerReference = null;
        foreach ($details as $detail) {

            $bookingItem  = BookingItem::with('booking')->find($detail['booking_item_id']);

            if ($detail['type'] == 'child' && $detail['parent_id'] == null) {
                $detail['parent_id'] = $bookingItem->booking->customer_id;
            }

            $customer = Customer::updateOrCreate(
                [
                    'id' => $detail['customer_id'],
                ],
                [
                    'full_name' => $detail['full_name'],
                    'email_address' => $detail['email_address'],
                    'contact_number' => $detail['contact_number'],
                    'age' => $detail['age'],
                    'parent_id' => $detail['parent_id'],
                    'contact_number' => $detail['contact_number'],
                ]
            );


            // dd($bookingItem);
            $pivotData = [];
            $pivotData[$customer->id] = [
                'team_id' => $bookingItem->booking->team_id,
                'is_lead' => $detail['is_lead'],

            ];

            // Sync with pivot data
            $bookingItem->customers()->syncWithoutDetaching($pivotData);

            CustomerManifest::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'booking_item_id' => $detail['booking_item_id'],
                ],
                [
                    'position' => $detail['position'],
                    'notes' => $detail['notes'],
                    'is_indemnity' => $detail['is_indemnity'],
                    'type' => $detail['type'],
                    'team_id' => $bookingItem->booking->team_id,
                ]
            );
            // if ($detail['discount_id'] != null) {
            //     CustomerDiscount::updateOrCreate(
            //         [
            //             'customer_id' => $customer->id,
            //             'booking_discount_item_id' => $detail['discount_id'],
            //         ]
            //     );
            // }
            if ($detail['index'] == 1) {
                $customerId = $customer->id;
                $customerReference = $bookingItem->booking->reference;
                $bookingItemForLink = $bookingItem;
            }
        }

        $expiresAt = $this->resolveCheckinExpiry($bookingItemForLink ?? null);
        if (!$expiresAt) {
            return Inertia::render('Front/BookingQuestionnaire/Expired', [
                'message' => 'Activity start time is not set. Please contact your booking agent to confirm activity start time.',
                'activityDate' => $bookingItemForLink?->booking_inventory?->getRawOriginal('date'),
                'activityOver' => false,
            ]);
        }

        $params = array_filter([
            'reference' => $customerReference,
            'customer' => $customerId,
            'booking_item_id' => $bookingItemForLink?->id,
        ], static fn ($value) => $value !== null && $value !== '');

        $url = URL::temporarySignedRoute(
            'front.booking-questionnaire.checkin',
            $expiresAt,
            $params
        );

        return Inertia::location($url);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $manifests = $request->all();
        // dd($manifests['customer_manifests']);
        foreach ($manifests['customer_manifests'] as $manifest) {
            if (isset($manifest['index']) && $manifest['index'] == 1) {
                $validated = $manifest;
                // $manifest->validate([
                //     'booking_id' => 'required|integer', //   "booking_id" => 4
                //     "booking_item_id" => 'required|integer',
                //     "customer_id"  => 'required|integer',
                //     'lead_guide_id' => 'nullable|integer', //   "lead_guide_id" => "6"
                //     'safety_guide_id' => 'nullable|integer', //   "safety_guide_id" => "6"
                //     'type_group_id' => 'nullable|integer', //  "type_group_id" => "4"
                //     'full_name' => 'string', //     "first_name" => "Customer9"
                //     'age' => 'nullable|integer', //    "date_of_birth" => null
                //     'referral_id' => 'nullable|integer', //    "date_of_birth" => null
                //     'continent_id' => 'nullable|integer', //   "continent_id" => "5"
                //     'country_id' => 'nullable|integer', //   "country_id" => "44"
                //     'province_id' => 'nullable|integer', //   "province_id" => "714"
                //     'medical_id' => 'nullable|string', //   "medical_id" => "Commodi facere qui t"
                //     'medical_detail' => 'nullable|string', //    "medical_detail" => "Commodi facere qui t"
                //     'is_fit' => 'nullable|integer', //   "is_fit" => "2"
                //     'is_agree' => 'nullable|integer', //   "is_agree" => "2"
                //     'email_address' => 'nullable|email', //     "email_address" => "kylamyf@mailinator.com"
                //     'signature' => 'nullable|string', //    "medical_detail" => "Commodi facere qui t"
                //     'is_minor' => 'nullable|integer', //   "is_minor" => "2"
                //     'children' => 'nullable|array',
                //     'gender_id' => 'nullable|integer', //   "gender_id" => "Sint aliqua Ipsa s"
                //     'child_name' => 'nullable|string', //   "child_name" => "Plato Bryan"
                //     'child_age' => 'nullable|integer', //   "child_age" => "Enim do do perspicia"
                //     'helmet_size' => 'nullable|string', //   "helmet_size" => "Quidem quisquam ulla"
                //     'recieve_invite' => 'nullable|integer', //    "recieve_invite" => "2"
                //     'pully_number' => 'nullable|string', //   "pully_number" => "320"
                // ]);
            }
        }

        try {
            $booking = DB::transaction(function () use ($validated) {
                $bookingItem = BookingItem::find($validated['booking_item_id']);
                $booking = Booking::find($bookingItem->booking_id);
                $customer = Customer::find($validated['customer_id']);

                if (!$customer) {
                    if (empty($validated['full_name'])) {
                        throw new \Exception('Customer not found and full name is missing.');
                    }

                    $customer = Customer::create([
                        'full_name' => $validated['full_name'],
                        'email_address' => $validated['email_address'] ?? null,
                        'contact_number' => $validated['contact_number'] ?? null,
                        'age' => $validated['age_id'] ?? null,
                        'parent_id' => $validated['parent_id'] ?? null,
                        'team_id' => $booking->team_id ?? null,
                    ]);

                    $bookingItem->customers()->syncWithoutDetaching([
                        $customer->id => [
                            'team_id' => $booking->team_id,
                            'is_lead' => (int) ($validated['is_lead'] ?? 0),
                        ],
                    ]);

                    $validated['customer_id'] = $customer->id;
                } else {
                    $customer->update([
                        'full_name' => $validated['full_name'],
                        'email_address' => $validated['email_address'],
                        'age' => $validated['age_id'],
                        'parent_id' => $validated['parent_id'],
                    ]);
                }
                //dd('here');

                // $is_minor = $validated['is_minor'] ?? null;
                // $children = $validated['children'] ?? null;
                // if ($is_minor == 1 && $children != null) {

                //     $validated['children'] = collect($validated['children']);
                //     $validated['children']->each(function ($child) use ($customer) {
                //         $child['parent_id'] = $customer->id;
                //         $child['full_name'] = $child['child_name'];
                //         $child['age'] = $child['child_age'];
                //         Customer::create($child);
                //     });
                // }

                // $questionaire_answers = QuestionnaireAnswer::create([
                //     'questionnaire_id' => $validated['questionnaire_id'],
                //     'questionnaire_field_id' => $validated['questionnaire_field_id'],
                //     'customer_id' => $validated['customer_id'],
                //     'value' => $validated['value'],
                //     'team_id' => $validated['team_id'],
                // ]);
                $questions = Question::whereHas('question_sites', function ($q) use ($bookingItem) {
                    $q->where('service_id', $bookingItem->service_id)
                        ->where('enabled', 1)
                        ->orderBy('sort_order');
                })
                    ->with('question_sites')
                    ->where('type', 'indemnity')
                    ->get();

                foreach ($validated as $key => $value) {

                    $q = $questions->firstWhere('slug', $key);
                    if (!$q) continue;
                    if ($key == 'signature') {
                        $value = null;
                    };
                    QuestionAnswer::updateOrCreate(
                        [
                            'booking_item_id' => $validated['booking_item_id'],
                            'customer_id'     => $validated['customer_id'],
                            'question_id'     => $q->id,
                        ],
                        [
                            'answer'          => $value,
                        ]
                    );
                }


                // $bookingQuestionnaire = BookingQuestionnaire::updateOrCreate(
                //     [
                //         'booking_item_id' => $bookingItem->id,
                //         'customer_id' => $customer->id,
                //     ],
                //     [
                //         'guide_id' => $validated['lead_guide_id'] ?? null,
                //         'safety_guide_id' => $validated['safety_guide_id'] ?? null,
                //         'type_group_id' => $validated['type_group_id'] ?? null,
                //         'signature' => null,
                //         'age' => $validated['age'] ?? null,
                //         'type_referral' => $validated['referral_id'] ?? null,
                //         'country_id' => $validated['country_id'] ?? null,
                //         'province_id' => $validated['province_id'] ?? null,
                //         'is_medical' => $validated['medical_detail'] != "" ? 1 : 0,
                //         'medical_detail' => $validated['medical_detail'] ?? null,
                //         'is_fit' => $validated['is_fit'] ?? null,
                //         'is_minor' => $validated['is_minor'] ?? null,
                //         'is_agree' => $validated['is_agree'] ?? null,
                //         'helmet_size' => $validated['helmet_size'] ?? null,
                //         'children' => $validated['children'] ?? null,
                //         'gender_id' => $validated['gender_id'] ?? null,
                //         'recieve_invite' => $validated['recieve_invite'] ?? null,
                //         'pully_number' => $validated['pully_number'] ?? null,
                //     ]
                // );
                CustomerManifest::updateOrCreate(
                    [
                        'customer_id' => $validated['customer_id'],
                        'booking_item_id' => $validated['booking_item_id'],
                    ],
                    [
                        'is_indemnity' => 1,
                        'team_id' => $bookingItem?->booking?->team_id ?? $bookingItem?->team_id ?? $validated['team_id'] ?? null,
                    ]
                );


                // Optionally save the signature as a file
                if (!empty($validated['signature'])) {
                    // $questionAnswerSignature = QuestionAnswer::where('booking_item_id', $validated['booking_item_id'],)
                    //     ->where('customer_id', $validated['customer_id'])
                    //     ->whereIsNull('signature')
                    //     ->first();
                    $image = str_replace('data:image/png;base64,', '', $validated['signature']);
                    $image = str_replace(' ', '+', $image);
                    $filename = 'signature_' . time() . '.png';
                    \Storage::disk('public')->put("signatures/{$filename}", base64_decode($image));

                    $signatureTypeFileId = TypeFile::where('name', 'signature')->value('id');
                    if (!$signatureTypeFileId) {
                        Log::warning('Signature type_file_id missing; saving file without type reference.', [
                            'booking_item_id' => $validated['booking_item_id'] ?? null,
                            'customer_id' => $validated['customer_id'] ?? null,
                            'team_id' => $bookingItem->team_id ?? null,
                        ]);
                    }

                    File::create([
                        'team_id' => $bookingItem->team_id,
                        'table_name' => 'customers',
                        'table_row_id' => $validated['customer_id'],
                        'file_name' => $filename,
                        'file_type' => 'image/png',
                        'type_file_id' => $signatureTypeFileId,
                        'path' => "signatures/{$filename}",
                    ]);

                    $q = $questions->firstWhere('slug', 'signature');
                    QuestionAnswer::updateOrCreate(
                        [
                            'booking_item_id' => $validated['booking_item_id'],
                            'customer_id'     => $validated['customer_id'],
                            'question_id'     => $q->id,
                        ],
                        [
                            'answer'          => $filename,
                        ]
                    );
                }
                if ($validated['medical_id'] == 1) {
                    $alert = Alert::create([
                        'alert_text' => 'Medical Condition',
                        'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                        'type_alert_id' => 5,
                        'team_id' => $booking->team_id,
                    ]);
                    $users = User::where('team_id', $booking->team_id)
                        ->whereHas('roles', function ($query) {
                            $query->whereIn('name', ['Manager', 'Consultant']);
                        })
                        ->with('roles')
                        ->get();
                    foreach ($users as $user) {
                        $user->alerts()->attach($alert->id); // ✅ attach by ID
                    }
                }
                if ($validated['is_fit'] == 2) {
                    $alert = Alert::create([
                        'alert_text' => 'Not Fit',
                        'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                        'type_alert_id' => 7,
                        'team_id' => $booking->team_id,
                    ]);
                    $users = User::where('team_id', $booking->team_id)
                        ->whereHas('roles', function ($query) {
                            $query->whereIn('name', ['Manager', 'Consultant']);
                        })
                        ->with('roles')
                        ->get();
                    foreach ($users as $user) {
                        $user->alerts()->attach($alert->id); // ✅ attach by ID
                    }
                }
                if ($validated['is_agree'] == 2) {
                    $alert = Alert::create([
                        'alert_text' => 'Did Not agree to terms and conditions',
                        'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                        'type_alert_id' => 8,
                        'team_id' => $booking->team_id,
                    ]);
                    $users = User::where('team_id', $booking->team_id)
                        ->whereHas('roles', function ($query) {
                            $query->whereIn('name', ['Manager', 'Consultant']);
                        })
                        ->with('roles')
                        ->get();
                    foreach ($users as $user) {
                        $user->alerts()->attach($alert->id); // ✅ attach by ID
                    }
                }
                // always show complete indemnity
                $alert = Alert::create([
                    'alert_text' => 'Check-in Complete',
                    'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                    'type_alert_id' => 9,
                    'team_id' => $booking->team_id,
                ]);
                $users = User::where('team_id', $booking->team_id)
                    ->whereHas('roles', function ($query) {
                        $query->whereIn('name', ['Manager', 'Consultant']);
                    })
                    ->with('roles')
                    ->get();
                foreach ($users as $user) {
                    $user->alerts()->attach($alert->id); // ✅ attach by ID
                }

                if (false) {
                    $questionAnswer = QuestionAnswer::where('booking_item_id', $customer->booking_item_id)->where('customer_id', $customer->customer_id)->get();
                    // $bookingQuestionnaire->load('booking_item', 'booking_item.booking', 'customer', 'gender', 'province', 'country', 'continent');
                    $guide = User::find($validated['lead_guide_id']);
                    $safetyGuide = User::find($validated['safety_guide_id']);
                    $customer = Customer::find($validated['customer_id']);
                    $service = Service::with('team')->find($bookingItem?->service_id);

                    $clients = collect([
                        [
                            'sign_date'      => $questionAnswer->created_at ?? '',
                            'id_number'      => $questionAnswer->id_number ?? '',
                            'full_name'      => $questionAnswer?->full_name ?? '',
                            'age'            => $questionAnswer?->age_id ?? '',
                            'phone'          => $customer?->contact_number ?? '',
                            'email'          => $customer?->email_address ?? '',
                            'gender'         => $questionAnswer->gender?->name ?? '',
                            'country'        => $questionAnswer->country?->name ?? '',
                            'province'       => $questionAnswer->province?->name ?? '',
                            'is_medical'     => $questionAnswer->is_medical ? 'Yes' : 'No',
                            'medical_detail' => $questionAnswer->medical_detail ?? '',
                            'is_fit'         => $questionAnswer->is_fit ? 'Yes' : 'No',
                            'is_minor'       => $questionAnswer->is_minor ? 'Yes' : 'No',
                            'signature'      => $questionAnswer->signature
                                ? public_path("storage/signatures/{$questionAnswer->signature}")
                                : null,
                        ],
                    ]);

                    $year = now()->format('Y');

                    $pdf = Pdf::loadView(
                        count($clients) > 1 ? 'pdfs.indemnity-multi' : 'pdfs.indemnity-single',
                        compact('booking', 'clients', 'service', 'year')
                    );

                    $filename = 'indemnities/indemnity-' . $booking->reference . '.pdf';
                    Storage::disk('public')->put($filename, $pdf->output());
                    File::create([
                        'team_id'      => $booking->team_id,
                        'table_name'   => 'question_answers',
                        'table_row_id' => $questionAnswer->id,
                        'file_name'    => $booking->reference . '.pdf',
                        'file_type'    => "application/pdf",
                        'type_file_id' => 5,
                        'path'         => $filename,
                        'booking_id'   => $booking->id,
                    ]);

                    $subject = "Indemnity Form - {$booking->reference}";
                    $recipient = $customer->email_address;


                    $content = "
                        <p>Hello {$customer->full_name},</p>
                        <p>Your indemnity form is attached.</p>
                        <p>Thank you for choosing Canopy Tours.</p>
                        <p>Your booking reference is {$booking->reference}.</p>
                        <p>Please Contact us if you have any questions.</p>";
                    $attachments = [
                        [
                            'data' => $pdf->output(),
                            'name' => "indemnity-{$booking->reference}.pdf",
                            'mime' => 'application/pdf',
                        ],
                    ];

                    Email::create([
                        'sender'         => config('mail.from.address'),
                        'receiver'       => $recipient,
                        'subject'        => $subject,
                        'body'           => strip_tags($content), // or a summary
                        'attachment_path' => $filename,
                        'sent'           => true,
                        'sent_at'        => now(),
                        'user_id'        => null,
                        'auditable_type' => 'App\Models\CustomerManifest',
                        'auditable_id'   => $customerManifest->id,
                        'team_id'        => $service->team->id,
                    ]);

                    Mail::to($recipient)->send(new CheckInMail([
                        'subject' => $subject,
                        'content' => $content,
                    ], $attachments));
                }

                return $booking;
            });

            $bookingItemId = collect($manifests['customer_manifests'] ?? [])->first()['booking_item_id'] ?? null;
            $bookingItem = $bookingItemId
                ? BookingItem::with(['booking', 'booking_inventory', 'customer_manifests'])->find($bookingItemId)
                : null;

            if ($bookingItem) {
                $expectedCount = (int) ($bookingItem->pax_adults ?? 0) + (int) ($bookingItem->pax_children ?? 0);
                $completedCount = $bookingItem->customer_manifests()
                    ->where('is_indemnity', 1)
                    ->count();

                $params = [
                    'booking_item_id' => $bookingItem->id,
                    'reference' => $bookingItem->booking?->reference,
                ];
                if ($expectedCount > 0 && $completedCount >= $expectedCount) {
                    $params['complete'] = 1;
                }

                $expiresAt = $this->resolveCheckinExpiry($bookingItem);
                if (!$expiresAt) {
                    return Inertia::render('Front/BookingQuestionnaire/Expired', [
                        'message' => 'Activity start time is not set. Please contact your booking agent to confirm activity start time.',
                        'activityDate' => $bookingItem?->booking_inventory?->getRawOriginal('date'),
                        'activityOver' => false,
                    ]);
                }

                $url = URL::temporarySignedRoute(
                    'front.booking-questionnaire.checkin',
                    $expiresAt,
                    $params
                );

                return Inertia::location($url);
            }

            return redirect()->route('booking-questionnaire.index');
        } catch (\Throwable $e) {
            // Log the full error for debugging
            Log::error('Booking Questionnaire failed and rolled back', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);
            return back()->withInput()->with([
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ]);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function checkinDynamic(Request $request)
    {
        try {
            $bookingItem = BookingItem::with([
                'booking',
                'service',
                'customers' => function ($q) use ($request) {
                    $q->where('customers.id', $request->customer);
                },
                'customer_manifests',
            ])
                ->whereHas('booking', function ($q) use ($request) {
                    $q->where('reference', $request->reference);
                })
                ->whereHas('customers', function ($q) use ($request) {
                    $q->where('customers.id', $request->customer);
                })
                ->first();
            // dd($request->all());
            $user = auth()->user();
            $customerId = $request->customer ?? null; // your passed in customer ID

            if (!$bookingItem) {
                return response()->json([
                    'success' => false,
                    'error' => 'Booking not found',
                ], 200);
            }

            $questionnaire = Questionnaire::where('service_id', $bookingItem->service_id)
                ->where('season_id', $bookingItem->season_id)
                ->first();
            if (!$questionnaire && $bookingItem->season_id) {
                $season = Season::find($bookingItem->season_id);
                if ($season) {
                    app(QuestionnaireSeeder::class)->seedForSeason($season);
                }
            }
            //$booking = Booking::findOrFail($booking->id);


            // $bookingItem = BookingItem::where('booking_id', $booking->id)->first();
            // $bookingItem->load([
            //     'service',
            //     'season',
            //     'inventory',
            //     //'customers',  :id,name,email,contact_number
            //     //'customer_manifests',  :id,position,notes,is_indemnity,customer_id,booking_id\
            //     'booking_infos', // :id,booking_id,type_location_id,location,time,created_at,updated_at,deleted_at
            //     //'customers.booking_questionnaire',  :id,booking_id,customer_id,guide_id,safety_guide_id,type_group_id,fullname,email,signature,birth_date,type_referral,country_id,province_id,is_medical,medical_detail,is_fit,is_agree
            //     'booking_detail', //:id,booking_id ,start_time,end_time
            //     'booking_fee', //:id,booking_id,tot_amount
            //     // :id,booking_id,amount
            //     'booking_inventory', // :id,booking_id,inventory_id
            //     'booking_staff', // :id,booking_id,user_id
            //     'booking_staff.user', // :id,name,email,contact_number
            //     // Filter customers by ID
            //     'customers' => function ($q) use ($customerId) {
            //         $q->where('customers.id', $customerId);
            //     },
            //     // Filter customer manifests by customer ID
            //     'customer_manifests' => function ($q) use ($customerId) {
            //         $q->where('customer_id', $customerId);
            //     },
            //     // Filter nested relation (booking_questionnaire) from customers
            //     'customers.booking_questionnaire' => function ($q) use ($customerId) {
            //         $q->where('customer_id', $customerId);
            //     },
            // ])
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
            // ->get();

            $leadGuides = User::with('roles:id,name')
                ->whereHas('roles', function ($query) {
                    $query->whereIn('id', [5]);
                })
                ->when($bookingItem->booking->team_id, function ($query, $teamId) {
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
                ->when($bookingItem->booking->team_id, function ($query, $teamId) {
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

            $typeGroups = TypeGroup::select('id', 'name')->get();
            $ageGroups = collect(Questionnaire::AGES);
            $typeGender = collect(Questionnaire::GENDERS);
            $documents = collect(Questionnaire::DOCUMENTS);
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
                'booking' => $bookingItem->booking,
                'bookingItem' => $bookingItem,
                'leadGuides' => $leadGuides,
                'safetyGuides' => $safetyGuides,
                'typeGroups' => $typeGroups,
                'ageGroups' => $ageGroups,
                'typeGender' => $typeGender,
                'documents' => $documents,
                'typeReferral' => $typeReferral,
                'typeConfirmation' => $typeConfirmation,
                'questionnaire' => $questionnaireFlat,
                'team' => $team,
            ]);
        } catch (InvalidSignatureException $e) {
            // return Inertia::render('Front/BookingQuestionnaire/Expired', [
            //     'message' => 'Your check-in link has expired. Please restart the process.'
            // ]);
            return redirect()->route('booking-questionnaire.index');
        }
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
