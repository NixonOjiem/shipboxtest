<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserContoller extends Controller
{
    //validation
    private function validateRole(Request $request)
    {
        return $request->validate([
            'role_name' => 'required|string|exists:roles,name',
        ]);
    }

    //assign a user a role
    public function assignRole(Request $request, User $user)
    {
        $this->authorize('update users');
        $data = $this->validateRole($request);

        $user->assignRole($data['role_name']);

        return response()->json([
            'message' => 'Role assigned',
            'roles' => $user->getRoleNames()
        ], 201);
    }

    //remove a role from user
    public function removeRole(Request $request, User $user)
    {
        $this->authorize('update users');
        $data = $this->validateRole($request);

        $user->removeRole($data['role_name']);

        return response()->json([
            'message' => 'Role removed',
            'roles' => $user->getRoleNames()
        ], 200);
    }
}
