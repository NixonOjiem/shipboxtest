<?php

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserContoller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Routes to manage permissions on roles
// Route::middleware(['auth:sanctum', 'can:update users'])->group(function () {
//     Route::post('/roles/{role}/give-permission', [RoleController::class, 'givePermission']);
//     Route::post('/roles/{role}/revoke-permission', [RoleController::class, 'revokePermission']);
// });



// test route to check if the server is running
Route::get('/testserver', function () {
    return response()->json(['messages' => 'Hi, server running']);
});

// Authentication routes
Route::post('/login', [AuthenticationController::class, 'login']);
Route::post('/signup', [AuthenticationController::class, 'signup']);

// Route to assign and revoke roles || Routes to manage permissions on roles
Route::middleware(['auth:sanctum', 'can:update users'])->group(function () {

    // Route to assign and revoke roles
    Route::post('/users/{user}/assign-role', [UserContoller::class, 'assignRole']);
    Route::post('/users/{user}/remove-role', [UserContoller::class, 'removeRole']);

    //Routes to manage permissions on roles
    Route::post('/roles/{role}/give-permission', [RoleController::class, 'givePermission']);
    Route::post('/roles/{role}/revoke-permission', [RoleController::class, 'revokePermission']);

});


// Products routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/product-post', [ProductController::class, 'store'])->middleware('can:create products');
    Route::post('/products/{product}/delete', [ProductController::class, 'destroy'])->middleware('can:delete products');
    Route::patch('/products/{product}/modify-price', [ProductController::class, 'modifyPrice'])->middleware('can:update products');
    Route::patch('/ptoducts/{product}/modify-name', [ProductController::class, 'modifyName'])->middleware('can:update products');
    Route::delete('/product/{product}/delete', [ProductController::class, 'destroy'])->middleware('can:delete products');
});

//order routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/order-post', [OrderController::class, 'createOrder'])->middleware('can:create orders');
    Route::get('/order-fetch', [OrderController::class, 'fetchOrders'])->middleware('can:read orders');
    Route::post('/order/{order}/delete', [OrderController::class, 'deleteOrder'])->middleware('can:delete orders');
    Route::patch('/order/{order}/update', [OrderController::class, 'updateOrder'])->middleware('can:update orders');
});

