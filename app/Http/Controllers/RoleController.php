<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return ResponseHelper::jsonResponse(true, 'Roles retrieved', $roles, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'sanctum']);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();
            return ResponseHelper::jsonResponse(true, 'Role created successfully', $role->load('permissions'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function show($id)
    {
        $role = Role::with('permissions')->find($id);

        if (!$role) {
            return ResponseHelper::jsonResponse(false, 'Role not found', null, 404);
        }

        return ResponseHelper::jsonResponse(true, 'Role retrieved', $role, 200);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return ResponseHelper::jsonResponse(false, 'Role not found', null, 404);
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($role->name === 'superadmin') {
            return ResponseHelper::jsonResponse(false, 'Cannot modify superadmin role directly from here.', null, 403);
        }

        DB::beginTransaction();
        try {
            $role->name = $request->name;
            $role->save();

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();
            return ResponseHelper::jsonResponse(true, 'Role updated successfully', $role->load('permissions'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return ResponseHelper::jsonResponse(false, 'Role not found', null, 404);
        }

        if (in_array($role->name, ['superadmin', 'manager', 'hr', 'employee', 'finance'])) {
            return ResponseHelper::jsonResponse(false, 'Cannot delete system default roles.', null, 403);
        }

        try {
            $role->delete();
            return ResponseHelper::jsonResponse(true, 'Role deleted successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    public function getPermissions()
    {
        $permissions = Permission::all()->groupBy(function ($item) {
            return explode('-', $item->name)[0];
        });

        return ResponseHelper::jsonResponse(true, 'Permissions retrieved', $permissions, 200);
    }
}
