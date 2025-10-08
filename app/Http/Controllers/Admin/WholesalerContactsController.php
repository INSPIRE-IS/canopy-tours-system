<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wholesaler;
use App\Models\WholesalerContact;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class WholesalerContactsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('wholesaler_contacts_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');

        $wholesalercontacts = WholesalerContact::with(['wholesaler'])
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        $wholesalers = Wholesaler::select('name', 'id')->get();

        return Inertia::render('Admin/WholesalerContacts/Index', [
            'wholesalercontacts' => $wholesalercontacts,
            'wholesalers' => $wholesalers ?? [],
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'type' => 'required|string|max:255',
            'wholesaler_id' => 'required|exists:wholesalers,id',
        ]);

        $validated['team_id'] = auth()->user()->team_id;

        $wholesalerContact = WholesalerContact::create($request->all());
        // $wholesalerContact->users()->sync($request->input('users', []));

        return redirect()->route('admin.wholesaler-contacts.index');
    }

    public function update(Request $request, WholesalerContact $wholesalerContact)
    {
        dd('not implemented');
        $wholesalerContact->update($request->all());
        $wholesalerContact->users()->sync($request->input('users', []));

        return redirect()->route('admin.wholesaler-contacts.index');
    }

    public function show(WholesalerContact $wholesalerContact)
    {
        dd('not implemented');
        abort_if(Gate::denies('wholesaler_contacts_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //TODO have a page to preview the alert
        // $wholesalerContact->load('users');
        // return view('admin.wholesalerContacts.show', compact('wholesalerContact'));
    }

    public function destroy(WholesalerContact $wholesalerContact)
    {
        abort_if(Gate::denies('wholesaler_contacts_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $wholesalerContact->delete();

        return back();
    }
}
