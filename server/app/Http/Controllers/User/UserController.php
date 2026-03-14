<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    //handle middleware for the methods
    public function __construct()
    {
        $this->middleware('can:update users');
    }
    //validation
    private function validateRole(Request $request)
    {
        return $request->validate([
            'role_name' => 'required|string|exists:roles,name',
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/users/{user}/assign-role",
     * summary="Assign a new role to a user",
     * description="Adds a role to the user without removing existing ones. Requires 'update users' permission.",
     * tags={"User Management"},
     * @OA\Parameter(
     * name="user",
     * in="path",
     * description="ID of the user",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"role_name"},
     * @OA\Property(property="role_name", type="string", example="writer", description="The name of the role existing in the database")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Role assigned successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Role assigned"),
     * @OA\Property(property="roles", type="array", @OA\Items(type="string", example="writer"))
     * )
     * ),
     * @OA\Response(response=422, description="Validation error - role name is required or does not exist"),
     * @OA\Response(response=403, description="Forbidden - User lacks 'update users' permission")
     * )
     */

    public function assignRole(Request $request, User $user)
    {
        Log::info("the request recieved is:", $request->all());
        // Authorization is now handled by the 'can' middleware in api.php
        $data = $this->validateRole($request);

        $user->assignRole($data['role_name']);

        return response()->json([
            'message' => 'Role assigned',
            'roles' => $user->getRoleNames()
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/users/{user}/change-role",
     * summary="Sync/Replace user roles",
     * description="Wipes all existing roles and replaces them with the new specified role. Requires 'update users' permission.",
     * tags={"User Management"},
     * @OA\Parameter(
     * name="user",
     * in="path",
     * description="ID of the user",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"role_name"},
     * @OA\Property(property="role_name", type="string", example="admin")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Role updated successfully",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="role changed successfully"),
     * @OA\Property(property="role", type="array", @OA\Items(type="string", example="admin"))
     * )
     * ),
     * @OA\Response(response=404, description="User not found"),
     * @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function changeRole(Request $request, user $user)
    {
        //validate data
        $data = $this->validateRole($request);

        //sync wipes old role validate new ones
        $user->syncRoles($data['role_name']);

        return response()->json([
            'message' => 'role changed successfully',
            'role' => $user->getRoleNames()
        ], 200);
    }
}
