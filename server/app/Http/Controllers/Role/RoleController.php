<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private function validatedPermission(Request $request)
    {
        return $request->validate([
            'permission_name' => 'required|string|exists:permissions,name',
        ]);
    }

    public function givePermission(Request $request, Role $role)
    {
        // Authorization handled by 'can:update roles' middleware in api.php
        $data = $this->validatedPermission($request);

        $role->givePermissionTo($data['permission_name']);

        return response()->json([
            'message' => 'Permission added to role',
            'permissions' => $role->permissions->pluck('name')
        ]);
    }

    public function revokePermission(Request $request, Role $role)
    {
        // Authorization handled by 'can:update roles' middleware in api.php
        $data = $this->validatedPermission($request);

        $role->revokePermissionTo($data['permission_name']);

        return response()->json([
            'message' => 'Permission removed from role',
            'permissions' => $role->permissions->pluck('name')
        ]);
    }
}
