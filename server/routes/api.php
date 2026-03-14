<?php

use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\Stock\StockController;
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

    // Products routes --midleware moved inside the controller waiting test
    Route::post('/product-post', [ProductController::class, 'createProduct']);
    Route::patch('/products/{product}', [ProductController::class, 'updateProductDetails']);
    Route::delete('/products/{product}/delete', [ProductController::class, 'deleteProduct']);
    Route::get('/products', [ProductController::class, 'fetchProducts']);
    Route::patch('/products/{product}/adjust', [StockController::class, 'adjust']);

    //order routes --middleware moved to controller awaiting testing
    Route::post('/order-post', [OrderController::class, 'createOrder']);
    Route::get('/order-fetch', [OrderController::class, 'fetchOrders']);
    Route::patch('/orders/{order}/update', [OrderController::class, 'updateOrder']);
    Route::delete('/orders/{order}/delete', [OrderController::class, 'deleteOrder']);

    // Route to assign and revoke roles || Routes to manage permissions on roles

    // Routes to manage permissions on roles
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/users/{user}/change-role', [UserController::class, 'changeRole']);

    //Routes to manage permissions on roles
    Route::post('/roles/{role}/give-permission', [RoleController::class, 'givePermission']);
    Route::post('/roles/{role}/revoke-permission', [RoleController::class, 'revokePermission']);

});












// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Routes to manage permissions on roles
// Route::middleware(['auth:sanctum', 'can:update users'])->group(function () {
//     Route::post('/roles/{role}/give-permission', [RoleController::class, 'givePermission']);
//     Route::post('/roles/{role}/revoke-permission', [RoleController::class, 'revokePermission']);
// });

