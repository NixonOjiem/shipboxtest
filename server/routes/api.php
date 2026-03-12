<?php

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// test route to check if the server is running
Route::get('/testserver', function () {
    return response()->json(['messages' => 'Hi, server running']);
});

//Public Authentication Routes
Route::post('/login', [AuthenticationController::class, 'login']);
Route::post('/signup', [AuthenticationController::class, 'signup']);

// Products routes
Route::middleware(['auth:sanctum'])->group(function () {
    // logout route
    Route::post('/logout', [AuthenticationController::class, 'logout']);
    // Products routes
    Route::post('/product-post', [ProductController::class, 'store'])->middleware('can:create products');
    //Route::post('/products/{product}/delete', [ProductController::class, 'destroy'])->middleware('can:delete products');
    Route::patch('/products/{product}/modify-price', [ProductController::class, 'modifyPrice'])->middleware('can:update products');
    Route::patch('/products/{product}/modify-name', [ProductController::class, 'modifyName'])->middleware('can:update products');
    Route::delete('/products/{product}/delete', [ProductController::class, 'destroy'])->middleware('can:delete products');

    //order routes
    Route::post('/order-post', [OrderController::class, 'createOrder'])->middleware('can:create orders');
    Route::get('/order-fetch-all', [OrderController::class, 'fetchAllOrders'])->middleware('role:admin');
    Route::get('/order-fetch/{user}/seller-orders', [OrderController::class, 'fetchSellerOrders'])->middleware('can:read orders');
    Route::delete('/orders/{order}/delete', [OrderController::class, 'deleteOrder'])->middleware('can:delete orders');
    Route::patch('/orders/{order}/update', [OrderController::class, 'updateOrder'])->middleware('can:update orders');

    // Route to assign and revoke roles || Routes to manage permissions on roles
    Route::middleware('can:update users')->group(function () {
        // Routes to manage permissions on roles
        Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/users/{user}/remove-role', [UserController::class, 'removeRole']);
        //Routes to manage permissions on roles
        Route::post('/roles/{role}/give-permission', [RoleController::class, 'givePermission']);
        Route::post('/roles/{role}/revoke-permission', [RoleController::class, 'revokePermission']);
    });
});












// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Routes to manage permissions on roles
// Route::middleware(['auth:sanctum', 'can:update users'])->group(function () {
//     Route::post('/roles/{role}/give-permission', [RoleController::class, 'givePermission']);
//     Route::post('/roles/{role}/revoke-permission', [RoleController::class, 'revokePermission']);
// });

