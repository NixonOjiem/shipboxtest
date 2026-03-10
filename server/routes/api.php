<?php

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\User\UserContoller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// test route to check if the server is running
Route::get('/testserver', function () {
    return response()->json(['messages' => 'Hi, server running']);
});

// Authentication routes
Route::post('/login', [AuthenticationController::class, 'login']);
Route::post('/signup', [AuthenticationController::class, 'signup']);

// Route to assign and revoke roles
Route::middleware(['auth:sanctum', 'can:update users'])->group(function () {
    Route::post('/users/{user}/assign-role', [UserContoller::class, 'assignRole']);
    Route::post('/users/{user}/remove-role', [UserContoller::class, 'removeRole']);
});


