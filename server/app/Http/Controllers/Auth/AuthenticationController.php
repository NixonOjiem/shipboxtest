<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AuthenticationController extends Controller
{   /**
    * @OA\Post(
    * path="/api/login",
    * tags={"Authentication"},
    * summary="Login a user",
    * description="Authenticates a user and returns a Bearer token",
    * @OA\RequestBody(
    * required=true,
    * @OA\JsonContent(
    * required={"email", "password"},
    * @OA\Property(property="email", type="string", format="email", example="testuser@app.com"),
    * @OA\Property(property="password", type="string", format="password", example="secret123")
    * )
    * ),
    * @OA\Response(response=200, description="Successful login"),
    * @OA\Response(response=401, description="Invalid credentials"),
    * @OA\Response(response=422, description="Validation Error")
    * )
    */
    //Login function
    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        Log::info('user logged in', $user->toArray());
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/signup",
     * tags={"Authentication"},
     * summary="Register a new user",
     * description="Creates a new user record in the database",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name", "email", "password", "password_confirmation"},
     * @OA\Property(property="name", type="string", example="John Doe"),
     * @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="secret123"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     * )
     * ),
     * @OA\Response(response=201, description="User registered successfully"),
     * @OA\Response(response=422, description="Validation Error")
     * )
     */

    public function signup(Request $request)
    {
        // Log::info('Receiving request:', $request->all());

        // Validate incoming request
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            // include password_confirmation field in request
        ]);
        // Log::info('validated request:', $data);
        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Log::info('User created', $user->toArray());

        // Return response
        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * tags={"Authentication"},
     * summary="Logout user",
     * description="Revokes the user's access token",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="User logged out successfully"),
     * @OA\Response(response=401, description="Unauthenticated")
     * )
     */

    public function logout(Request $request)
    {
        // Revoke the token from all devices
        $request->user()->tokens()->delete();
        // revoke from the current device
        // $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'user logged out successfully']);

    }
}
