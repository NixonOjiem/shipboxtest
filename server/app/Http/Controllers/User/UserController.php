<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    //validation
    private function validateRole(Request $request)
    {
        return $request->validate([
            'role_name' => 'required|string|exists:roles,name',
        ]);
    }

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
