<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wholesaler;
use App\Models\WholesalerContact;
use Gate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $teamId = auth()->user()->team_id;

        $wholesalercontacts = WholesalerContact::with(['wholesaler'])
            ->where('team_id', $teamId)
            ->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        $wholesalers = Wholesaler::select('name', 'id')->where('team_id', $teamId)->get();

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
        $teamId = auth()->user()->team_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'type' => 'required|string|max:255',
            'wholesaler_id' => ['required', Rule::exists('wholesalers', 'id')->where('team_id', $teamId)],
        ]);

        $validated['team_id'] = $teamId;

        WholesalerContact::create($validated);
        // $wholesalerContact->users()->sync($request->input('users', []));

        return redirect()->route('admin.wholesaler-contacts.index');
    }

    public function update(Request $request, WholesalerContact $wholesalerContact)
    {
        $teamId = auth()->user()->team_id;
        abort_if((int) $wholesalerContact->team_id !== (int) $teamId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'type' => 'required|string|max:255',
            'wholesaler_id' => ['required', Rule::exists('wholesalers', 'id')->where('team_id', $teamId)],
        ]);

        // Always enforce team ownership server-side
        $validated['team_id'] = $teamId;

        $wholesalerContact->update($validated);

        // If/when you re-enable users
        // $wholesalerContact->users()->sync($request->input('users', []));

        return redirect()
            ->route('admin.wholesaler-contacts.index')
            ->with('success', 'Wholesaler contact updated successfully');
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
        abort_if((int) $wholesalerContact->team_id !== (int) auth()->user()->team_id, 403);

        $wholesalerContact->delete();

        return back();
    }
}
