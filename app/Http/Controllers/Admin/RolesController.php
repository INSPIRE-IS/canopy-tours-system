<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('roles_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $searchTerm = $request->get('search', '');
        $sortColumn = $request->get('sort', 'name'); // Default to 'name' if no sort is provided
        $sortDirection = $request->get('direction', 'asc');
        $roles = Role::with(['permissions'])->where('name', 'like', '%' . $searchTerm . '%')
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(25);

        $adminRole = Role::with('permissions')->find(1);
        $permissionList = Permission::select('id', 'name')->orderBy('name')->get();
        //dd(method_exists($user, 'can'));
        //dd($user->can('view permission'));
        /** @disregard P1013 */
        return Inertia::render('Admin/Roles/Index', [

            'roles' => $roles,
            'searchTerm' => $searchTerm,
            'sortColumn' => $sortColumn,
            'adminRole' => $adminRole,
            'permissionList' => $permissionList,
            'sortDirection' => $sortDirection,
            'can' => [
                'roles_show' => auth()->user()->can('roles_show'),
                'roles_create' => auth()->user()->can('roles_create'),
                'roles_edit' => auth()->user()->can('roles_edit'),
                'roles_delete' => auth()->user()->can('roles_delete'),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create($request->all());
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.roles.index');
    }



    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update($request->all());
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.roles.index');
    }

    public function show(Role $role)
    {
        abort_if(Gate::denies('role_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->load('permissions');

        return view('Admin.roles.show', compact('role'));
    }

    public function destroy(Role $role)
    {
        abort_if(Gate::denies('role_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $role->delete();

        return back();
    }
}
