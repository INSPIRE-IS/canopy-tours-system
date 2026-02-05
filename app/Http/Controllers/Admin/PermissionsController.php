<?php

namespace App\Http\Controllers\Admin;

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyPermissionRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PermissionsController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('permissions_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');
        $permission_list = Permission::where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(15);
        //dd(method_exists($user, 'can'));
        //dd($user->can('view permission'));

        /** @disregard P1013 */
        return Inertia::render('Admin/Permissions/Index', [
            'permission_list' => $permission_list,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'can' => [
                'permission_show' => auth()->user()->can('permissions_show'),
                'permission_edit' => auth()->user()->can('permissions_edit'),
                'permission_delete' => auth()->user()->can('permissions_delete'),
                'permission_create' => auth()->user()->can('permissions_create'),
            ],
        ]);
    }


    public function store(StorePermissionRequest $request)
    {
        $permission = Permission::create($request->all());
        return redirect()->route('permissions.index');
    }


    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $permission->update($request->all());

        return redirect()->route('permissions.index');
    }

    public function show(Permission $permission)
    {
        abort_if(Gate::denies('permission_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('Admin.permissions.show', compact('permission'));
    }

    public function destroy(Permission $permission)
    {
        abort_if(Gate::denies('permission_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $permission->delete();

        return back();
    }
}
