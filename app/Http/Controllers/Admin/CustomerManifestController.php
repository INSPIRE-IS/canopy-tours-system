<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCustomerManifestRequest;
use App\Http\Requests\StoreCustomerManifestRequest;
use App\Http\Requests\UpdateCustomerManifestRequest;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\CustomerDiscount;
use App\Models\CustomerManifest;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerManifestController extends Controller
{

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

        return redirect()->route('admin.customer-manifests.index');
    }

    public function edit(UpdateCustomerManifestRequest $request)
    {
        //dd($request->all());
        $validated = $request->validate([
            'customer_manifests' => 'required|array',
        ]);
        $details = $validated['customer_manifests'];


        //dd($details);
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
        }

        //$customerManifest->update($request->all());

        return redirect()->back()->with('success', 'Customer manifests updated successfully.');
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

        return redirect()->back()->with('success', 'Customer manifests updated successfully.');
    }

    public function destroy(CustomerManifest $customerManifest)
    {
        abort_if(Gate::denies('customer_manifest_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customerManifest->delete();

        return back();
    }
}
