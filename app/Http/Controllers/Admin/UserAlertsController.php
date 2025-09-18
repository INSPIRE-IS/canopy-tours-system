<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyUserAlertRequest;
use App\Http\Requests\StoreUserAlertRequest;
use App\Http\Requests\UpdateUserAlertRequest;
use App\Models\User;
use App\Models\UserAlert;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class UserAlertsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('user_alerts_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //abort_if(Gate::denies('team_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'alert_text'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');

        $userAlerts = UserAlert::with(['users'])
            ->where('alert_text', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(10);
        $users = User::select('name', 'id')->get();

        return Inertia::render('Admin/UserAlerts/Index', [
            'userAlerts' => $userAlerts,
            'users' => $users ?? [],
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(StoreUserAlertRequest $request)
    {
        $userAlert = UserAlert::create($request->all());
        $userAlert->users()->sync($request->input('users', []));

        return redirect()->route('admin.user-alerts.index');
    }

    public function update(UpdateUserAlertRequest $request, UserAlert $userAlert)
    {
        dd('not implemented');
        $userAlert->update($request->all());
        $userAlert->users()->sync($request->input('users', []));

        return redirect()->route('admin.user-alerts.index');
    }

    public function show(UserAlert $userAlert)
    {
        dd('not implemented');
        abort_if(Gate::denies('user_alert_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //TODO have a page to preview the alert
        // $userAlert->load('users');
        // return view('admin.userAlerts.show', compact('userAlert'));
    }

    public function destroy(UserAlert $userAlert)
    {
        abort_if(Gate::denies('user_alert_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $userAlert->delete();

        return back();
    }

    public function read(Request $request)
    {
        $alerts = \Auth::user()->userUserAlerts()->where('read', false)->get();
        foreach ($alerts as $alert) {
            $pivot       = $alert->pivot;
            $pivot->read = true;
            $pivot->save();
        }
    }
}
