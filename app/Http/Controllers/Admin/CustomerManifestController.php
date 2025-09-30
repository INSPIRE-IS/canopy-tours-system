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
        dd($request->all());
        $validated = $request->validate([
            'position' => 'required|string',
            'notes' => 'nullable|string',
            'is_indemnity' => 'boolean',
            'customer_id' => 'required|exists:customers,id',
            'booking_item_id' => 'required|exists:booking_items,id',
        ]);


        $customerManifest = CustomerManifest::create($request->all());

        return redirect()->route('admin.customer-manifests.index');
    }

    public function edit(UpdateCustomerManifestRequest $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'customer_manifests' => 'required|array',
        ]);
        $details = $validated['customer_manifests'];
        dd($details);

        //dd($details);
        foreach ($details as $detail) {

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

            $bookingItem  = BookingItem::find($detail['booking_item_id']);
            // dd($bookingItem);
            $pivotData = [];
            $pivotData[$customer->id] = [
                'team_id' => auth()->user()->team_id,   // TODO: get team from the page or session
            ];

            // Sync with pivot data
            $bookingItem->customers()->sync($pivotData);

            CustomerManifest::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'booking_item_id' => $detail['booking_item_id'],
                ],
                [
                    'position' => $detail['position'],
                    'notes' => $detail['notes'],
                    'is_indemnity' => $detail['is_indemnity'],
                ]
            );
            if ($detail['discount_id'] != null) {
                CustomerDiscount::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'booking_discount_item_id' => $detail['discount_id'],
                    ]
                );
            }
        }

        //$customerManifest->update($request->all());

        return redirect()->back()->with('success', 'Customer manifests updated successfully.');
    }

    public function destroy(CustomerManifest $customerManifest)
    {
        abort_if(Gate::denies('customer_manifest_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customerManifest->delete();

        return back();
    }
}
