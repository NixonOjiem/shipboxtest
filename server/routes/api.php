<?php

use App\Http\Controllers\Auth\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// test route to check if the server is running
Route::get('/testserver', function () {
    return response()->json(['messages' => 'Hi server running']);
});

// Authentication routes
Route::post('/login', [AuthenticationController::class, 'login']);
