<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyCustomerManifestRequest;
use App\Http\Requests\StoreCustomerManifestRequest;
use App\Http\Requests\UpdateCustomerManifestRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerManifest;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerManifestController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('customer_manifest_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customerManifests = CustomerManifest::with(['customer', 'booking', 'team'])->get();

        return view('admin.customerManifests.index', compact('customerManifests'));
    }

    public function create()
    {
        abort_if(Gate::denies('customer_manifest_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customers = Customer::pluck('full_name as name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bookings = Booking::pluck('lead_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.customerManifests.create', compact('bookings', 'customers'));
    }

    public function store(StoreCustomerManifestRequest $request)
    {
        $customerManifest = CustomerManifest::create($request->all());

        return redirect()->route('admin.customer-manifests.index');
    }

    public function edit(CustomerManifest $customerManifest)
    {
        abort_if(Gate::denies('customer_manifest_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customers = Customer::pluck('full_name as name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bookings = Booking::pluck('lead_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $customerManifest->load('customer', 'booking', 'team');

        return view('admin.customerManifests.edit', compact('bookings', 'customerManifest', 'customers'));
    }

    public function update(UpdateCustomerManifestRequest $request, CustomerManifest $customerManifest)
    {
        $customerManifest->update($request->all());

        return redirect()->route('admin.customer-manifests.index');
    }

    public function show(CustomerManifest $customerManifest)
    {
        abort_if(Gate::denies('customer_manifest_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customerManifest->load('customer', 'booking', 'team');

        return view('admin.customerManifests.show', compact('customerManifest'));
    }

    public function destroy(CustomerManifest $customerManifest)
    {
        abort_if(Gate::denies('customer_manifest_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $customerManifest->delete();

        return back();
    }
}
