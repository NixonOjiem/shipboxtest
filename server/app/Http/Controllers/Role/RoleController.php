<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    //handle middleware for the methods
    public function __construct()
    {
        $this->middleware('can:update users');
    }
    private function validatedPermission(Request $request)
    {
        return $request->validate([
            'permission_name' => 'required|string|exists:permissions,name',
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/roles/{role}/give-permission",
     * summary="Assign a permission to a specific role",
     * tags={"Roles & Permissions"},
     * @OA\Parameter(
     * name="role",
     * in="path",
     * description="The ID or Name of the role",
     * required=true,
     * @OA\Schema(type="string")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"permission_name"},
     * @OA\Property(property="permission_name", type="string", example="edit articles")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Permission successfully assigned",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Permission added to role"),
     * @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     * )
     * ),
     * @OA\Response(response=422, description="Validation error"),
     * @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function givePermission(Request $request, Role $role)
    {
        // Authorization handled by 'can:update roles' middleware in constructor
        $data = $this->validatedPermission($request);

        $role->givePermissionTo($data['permission_name']);

        return response()->json([
            'message' => 'Permission added to role',
            'permissions' => $role->permissions->pluck('name')
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/roles/{role}/revoke-permission",
     * summary="Remove a permission from a specific role",
     * tags={"Roles & Permissions"},
     * @OA\Parameter(
     * name="role",
     * in="path",
     * required=true,
     * @OA\Schema(type="string")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"permission_name"},
     * @OA\Property(property="permission_name", type="string", example="edit articles")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Permission successfully removed",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Permission removed from role"),
     * @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     * )
     * )
     * )
     */

    public function revokePermission(Request $request, Role $role)
    {
        // Authorization handled by 'can:update roles' middleware in constructor
        $data = $this->validatedPermission($request);

        $role->revokePermissionTo($data['permission_name']);

        return response()->json([
            'message' => 'Permission removed from role',
            'permissions' => $role->permissions->pluck('name')
        ]);
    }
}
