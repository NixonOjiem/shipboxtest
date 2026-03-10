<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function givePermission(Request $request, Role $role)
    {
        $this->authorize('update roles');
        $request->validate([
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        $role->givePermissionTo($request->permission_name);

        return response()->json([
            'message' => 'Permission added to role',
            'permissions' => $role->permissions->pluck('name')
        ]);
    }

    public function revokePermission(Request $request, Role $role)
    {
        $this->authorize('update roles');
        $request->validate([
            'permission_name' => 'required|string|exists:permissions,name',
        ]);

        $role->revokePermissionTo($request->permission_name);

        return response()->json([
            'message' => 'Permission removed from role',
            'permissions' => $role->permissions->pluck('name')
        ]);
    }
}
