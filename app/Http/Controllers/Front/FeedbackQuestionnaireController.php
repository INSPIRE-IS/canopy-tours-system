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
use App\Models\State;
use App\Models\TypeConfirmation;
use App\Models\TypeGroup;
use App\Models\TypeReferral;
use App\Models\User;
use App\Models\Season;
use App\Services\QuestionnaireSeeder;
use App\Notifications\IndemnityFlaggedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Storage;

class FeedbackQuestionnaireController extends Controller
{

    public function index(Request $request)
    {
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
        // dd($booking_items);

        $formatted = $booking_items->map(function ($booking_item) {

            //

            try {
                // Skip if manifests missing or incomplete
                $manifests = $booking_item->customer_manifests;
                //dd($manifests->isEmpty());
                if ($manifests->isEmpty()) return null;
                // if ($manifests->contains(fn($m) => !$m->is_indemnity)) return null;

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
        })
            ->filter()   // remove nulls
            ->values();
        //dd($formatted);
        return Inertia::render('Feedback-Welcome', [
            'bookings' => $formatted,
        ]);
    }


    public function signed(Request $request)
    {
        $validated = $request->validate([
            'booking_item_id' => 'required|integer',
            'customer_id' => 'required|integer',
        ]);

        if (!$validated) {
            return response()->json([
                'success' => false,
                'error' => 'Something went wrong while saving your booking. Please try again.',
            ], 500);
        }

        $url = URL::temporarySignedRoute(
            'front.feedback-questionnaire.feedback',
            now()->addMinutes(80),
            [
                'booking_item_id' => $request->booking_item_id,
                'reference' => $request->reference,
            ]
        );
        return Inertia::location($url);
    }

    public function feedback(Request $request)
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
                ->where('type', 'feedback')
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

                        Log::info('customer4', (array) $customer);

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

            $arrivals = collect(Question::ARRIVALS);
            $safetyBrifings = collect(Question::SAFETY_BRIFINGS);
            $drive = collect(Question::DRIVE);
            $areaKnowledge = collect(Question::AREA_KNOWLEDGE);
            $comfortSafety = collect(Question::COMFORT_SAFETY);


            $team = Team::select('id', 'name', 'description', 'color')->with('file_image', 'file_logo')->find($bookingItem->team_id);

            return Inertia::render('Feedback', [
                'booking' => $bookingItem->booking,
                'bookingItem' => $bookingItem,
                'leadGuides' => $leadGuides,
                'safetyGuides' => $safetyGuides,
                'arrivals' => $arrivals,
                'safetyBrifings' => $safetyBrifings,
                'drive' => $drive,
                'areaKnowledge' => $areaKnowledge,
                'comfortSafety' => $comfortSafety,
                'questions' => $questions,
                'team' => $team,
                'bookings' => $formatted,
            ]);
        } catch (InvalidSignatureException $e) {
            return Inertia::render('Front/BookingQuestionnaire/Expired', [
                'message' => 'Your check-in link has expired. Please restart the process.'
            ]);
            return redirect()->route('feedback-questionnaire.index');
        }
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $manifests = $request->all();

        foreach ($manifests['feedback'] as $manifest) {
            if (isset($manifest['index']) && $manifest['index'] == 1) {
                $validated = $manifest;
                //dd($validated);
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


                $questions = Question::whereHas('question_sites', function ($q) use ($bookingItem) {
                    $q->where('service_id', $bookingItem->service_id)
                        ->where('enabled', 1);
                })
                    ->with(['question_sites' => function ($q) use ($bookingItem) {
                        $q->where('service_id', $bookingItem->service_id)
                            ->where('enabled', 1)
                            ->orderBy('sort_order');
                    }])
                    ->where('type', 'feedback')
                    ->get();
                $flagged = [];
                foreach ($validated as $key => $value) {
                    if ($key == 'customer_id') continue;
                    $q = $questions->firstWhere('slug', $key);
                    if (!$q) continue;
                    if ($key == 'signature') {
                        $value = null;
                    };
                    if ($value == 1 || $value === "1" || $value === "No" || $value === "no") {
                        $flagged[] = [
                            'question' => $q->name ?? $q->slug,
                            'answer'   => $value,
                            'question_id' => $q->id,
                        ];
                    }


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
                $customerManifest = CustomerManifest::where('customer_id', $validated['customer_id'])
                    ->where('booking_item_id', $validated['booking_item_id'])
                    ->where('team_id', $validated['team_id'])
                    ->update([
                        'is_feedback' => 1,
                    ]);

                if (!empty($flagged)) {
                    Log::info('here 4');
                    // Recipients
                    $excoEmails = [
                        'exco1@example.com',
                        'exco2@example.com',
                    ];

                    // Main recipient + CC
                    Notification::route('mail', 'alerts@example.com')
                        ->notify(new IndemnityFlaggedNotification(
                            $customer,
                            $bookingItem,
                            $flagged,
                            $excoEmails
                        ));
                }


                // if ($validated['is_fit'] == 2) {
                //     $alert = Alert::create([
                //         'alert_text' => 'Not Fit',
                //         'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                //         'type_alert_id' => 7,
                //         'team_id' => $booking->team_id,
                //     ]);
                //     $users = User::where('team_id', $booking->team_id)
                //         ->whereHas('roles', function ($query) {
                //             $query->whereIn('name', ['Manager', 'Consultant']);
                //         })
                //         ->with('roles')
                //         ->get();
                //     foreach ($users as $user) {
                //         $user->alerts()->attach($alert->id); // ✅ attach by ID
                //     }
                // }

                // always show complete indemnity
                $alert = Alert::create([
                    'alert_text' => 'Feedback Complete',
                    'alert_link' => route('admin.bookings.index'), //route('front.payment.booking', ['booking' => $booking->id]),
                    'type_alert_id' => 10,
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
                Log::info('here 5');
                return $booking;
            });
            Log::info('feedback ' . var_export($validated, true));
            $bookingItemId = $validated['booking_item_id'] ?? null;
            $bookingDate = null;
            if ($bookingItemId) {
                $bookingItem = BookingItem::with('booking_inventory')->find($bookingItemId);
                $bookingDate = $bookingItem?->booking_inventory?->date;
            }

            return redirect()->route('front.feedback-questionnaire.index', array_filter([
                'booking_date' => $bookingDate,
            ]));
            //return redirect()->route('feedback-questionnaire.index');
        } catch (\Throwable $e) {
            // Log the full error for debugging
            Log::info('here 6');
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
}
