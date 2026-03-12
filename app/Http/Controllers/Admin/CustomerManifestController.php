<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCustomerManifestRequest;
use App\Http\Requests\StoreCustomerManifestRequest;
use App\Http\Requests\UpdateCustomerManifestRequest;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\CustomerDiscount;
use App\Models\CustomerManifest;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Question;
use App\Models\QuestionAnswer;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomerManifestController extends Controller
{
    private function logAudit(
        string $description,
        ?Booking $booking,
        ?BookingItem $bookingItem = null,
        array $properties = [],
        bool $logBooking = true,
        bool $logBookingItem = true
    ): void
    {
        $payload = [
            'description' => $description,
            'user_id' => auth()->id(),
            'properties' => json_encode($properties),
            'host' => request()->ip(),
        ];

        if ($logBooking && $booking) {
            AuditLog::create(array_merge($payload, [
                'subject_type' => Booking::class,
                'subject_id' => $booking->id,
            ]));
        }

        if ($logBookingItem && $bookingItem) {
            AuditLog::create(array_merge($payload, [
                'subject_type' => BookingItem::class,
                'subject_id' => $bookingItem->id,
            ]));
        }
    }

    public function add(StoreCustomerManifestRequest $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'position' => 'nullable|string',
            'type' => 'required|string',
            'notes' => 'nullable|string',
            'is_indemnity' => 'boolean',
            'customer_id' => 'nullable|exists:customers,id',
            'booking_item_id' => 'required|exists:booking_items,id',
            'discount_id' => 'nullable|exists:booking_discounts,id',
            'full_name' => 'nullable|string',
            'email_address' => 'nullable|email',
            'contact_number' => 'nullable|string',
            'age' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'discount_id' => 'nullable|integer',
            'is_lead' => 'boolean',
            'team_id' => 'nullable|integer',

        ]);

        $customer = Customer::find($validated['customer_id']);
        $bookingItem = BookingItem::find($validated['booking_item_id']);

        $customer = Customer::updateOrCreate(
            [
                'id' => $validated['customer_id'],
            ],
            [
                'full_name' => $validated['full_name'],
                'email_address' => $validated['email_address'],
                'contact_number' => $validated['contact_number'],
                'age' => $validated['age'],
                'parent_id' => $validated['parent_id'],
                'contact_number' => $validated['contact_number'],
            ]
        );

        $pivotData = [];
        $pivotData[$customer->id] = [
            'team_id' => $bookingItem->team_id,   // TODO: get team from the page or session
            'is_lead' => 1,
        ];

        // Sync with pivot data
        $bookingItem->customers()->sync($pivotData);

        CustomerManifest::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'booking_item_id' => $validated['booking_item_id'],
            ],
            [
                'position' => $validated['position'],
                'notes' => $validated['notes'],
                'is_indemnity' => $validated['is_indemnity'],
            ]
        );
        if ($validated['discount_id'] != null) {
            CustomerDiscount::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'booking_discount_item_id' => $validated['discount_id'],
                ]
            );
        }


        $customerManifest = CustomerManifest::updatedOrCreate(
            [
                'customer_id' => $customer->id,
                'booking_item_id' => $validated['booking_item_id'],
                'team_id' => $bookingItem->team_id,
            ],
            [
                'position' => $validated['position'],
                'notes' => $validated['notes'],
                'is_indemnity' => $validated['is_indemnity'],
            ]
        );

        $this->logAudit('Customer manifest added', $bookingItem->booking ?? null, $bookingItem, [
            'customer_id' => $customer->id,
            'booking_item_id' => $validated['booking_item_id'],
            'is_indemnity' => $validated['is_indemnity'] ?? null,
        ], false, true);

        return redirect()->route('admin.customer-manifests.index');
    }

    public function edit(UpdateCustomerManifestRequest $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'customer_manifests' => 'required|array',
        ]);

        try {
            $details = DB::transaction(function () use ($validated) {
                $details = $validated['customer_manifests'];
                //dd($details);
                foreach ($details as $detail) {

                    $bookingItem  = BookingItem::with('booking')->find($detail['booking_item_id']);
                    if ($detail['type'] == 'child' && $detail['parent_id'] != null) {
                        $parentDetails = $details;
                        foreach ($parentDetails as $parentDetail) {
                            if ($parentDetail['id'] ==  $detail['parent_id']) {
                                $parentEmail = $parentDetail['email_address'];
                            }
                        }
                        $parent = Customer::where('email_address', $parentEmail)->first();
                        $detail['parent_id'] = $parent->id;
                    } else if ($detail['type'] == 'child' && $detail['parent_id'] == null) {
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

                    $this->logAudit('Customer manifest updated', $bookingItem->booking ?? null, $bookingItem, [
                        'customer_id' => $customer->id,
                        'booking_item_id' => $detail['booking_item_id'],
                        'is_indemnity' => $detail['is_indemnity'] ?? null,
                        'position' => $detail['position'] ?? null,
                    ], false, true);
                }
                return $details;
            });
            return redirect()->back()->with('success', 'Customer manifests updated successfully.');
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
        }
    }

    public function editSingle(UpdateCustomerManifestRequest $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'customer_manifests' => 'required|array',
        ]);
        $detail = $validated['customer_manifests'];
        //dd($detail);

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
                'customer_id'     => $customer->id,
                'booking_item_id' => $detail['booking_item_id'],
            ],
            [
                'position'     => $detail['position'],
                'notes'        => $detail['notes'],
                'is_indemnity' => $detail['is_indemnity'],
                'type'         => $detail['type'],
                'team_id'         => $bookingItem->booking->team_id,
            ]
        );

        $this->logAudit('Customer manifest updated', $bookingItem->booking ?? null, $bookingItem, [
            'customer_id' => $customer->id,
            'booking_item_id' => $detail['booking_item_id'],
            'is_indemnity' => $detail['is_indemnity'] ?? null,
            'position' => $detail['position'] ?? null,
        ], false, true);

        return redirect()->back()->with('success', 'Customer manifests updated successfully.');
    }

    public function destroy(CustomerManifest $customerManifest)
    {
        abort_if(Gate::denies('customer_manifests_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customerManifest->delete();

        return back();
    }

    public function updateStatus(Request $request)
    {
        abort_if(Gate::denies('customer_manifests_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'booking_item_id' => 'required|exists:booking_items,id',
            'customer_id' => 'required|exists:customers,id',
            'attendance_status' => 'required|in:' . implode(',', CustomerManifest::ATTENDANCE_STATUSES),
            'notes' => 'nullable|string',
            'lunch_option' => 'nullable|string',
        ]);

        $bookingItem = BookingItem::with('booking')->findOrFail($validated['booking_item_id']);

        $manifestData = [
            'attendance_status' => $validated['attendance_status'],
            'team_id' => $bookingItem->booking->team_id,
        ];

        if ($request->has('notes')) {
            $manifestData['notes'] = $validated['notes'];
        }

        CustomerManifest::updateOrCreate(
            [
                'booking_item_id' => $validated['booking_item_id'],
                'customer_id' => $validated['customer_id'],
            ],
            $manifestData
        );

        if ($request->has('lunch_option')) {
            $questionId = Question::where('slug', 'lunch_option')->value('id');
            if ($questionId) {
                QuestionAnswer::updateOrCreate(
                    [
                        'question_id' => $questionId,
                        'booking_item_id' => $validated['booking_item_id'],
                        'customer_id' => $validated['customer_id'],
                    ],
                    [
                        'answer' => $validated['lunch_option'],
                    ]
                );
            }
        }

        $this->logAudit('Check-in detail updated', $bookingItem->booking ?? null, $bookingItem, [
            'customer_id' => $validated['customer_id'],
            'booking_item_id' => $validated['booking_item_id'],
            'attendance_status' => $validated['attendance_status'],
            'notes' => $validated['notes'] ?? null,
            'lunch_option' => $validated['lunch_option'] ?? null,
        ], false, true);

        return back()->with('success', 'Check-in details updated.');
    }
}
