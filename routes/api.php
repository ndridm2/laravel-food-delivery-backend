<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// user register
Route::post('/user/register', [App\Http\Controllers\Api\AuthController::class, 'userRegister']);

// restaurant register
Route::post('/restaurant/register', [App\Http\Controllers\Api\AuthController::class, 'restaurantRegister']);

// driver register
Route::post('/driver/register', [App\Http\Controllers\Api\AuthController::class, 'driverRegister']);

// login
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

// logout
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

// update latlong
Route::put('/user/update-latlong', [App\Http\Controllers\Api\AuthController::class, 'updateLatLong'])->middleware('auth:sanctum');

// update fcm_id
Route::put('/user/update-fcm', [App\Http\Controllers\Api\AuthController::class, 'updateFcmId'])->middleware('auth:sanctum');

// get all restaurant
Route::get('/restaurants', [App\Http\Controllers\Api\AuthController::class, 'getRestaurant']);

Route::apiResource('/products', App\Http\Controllers\Api\ProductController::class)->middleware('auth:sanctum');

// get product by userid
Route::get('/restaurant/{userId}/products', [App\Http\Controllers\Api\ProductController::class, 'getProductByUserId']);

//order
Route::post('/order', [App\Http\Controllers\Api\OrderController::class, 'createOrder'])->middleware('auth:sanctum');

// get payment method
Route::get('/payment-methods', [App\Http\Controllers\Api\OrderController::class, 'getPaymentMethod'])->middleware('auth:sanctum');

//get order by user id
Route::get('/order/user', [App\Http\Controllers\Api\OrderController::class, 'orderHistory'])->middleware('auth:sanctum');

//get order by restaurant
Route::get('/order/restaurant', [App\Http\Controllers\Api\OrderController::class, 'getOrdersByStatus'])->middleware('auth:sanctum');

//update order status by restaurant by id
Route::put('/order/restaurant/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatus'])->middleware('auth:sanctum');

//get order by driver
Route::get('/order/driver', [App\Http\Controllers\Api\OrderController::class, 'getOrdersByStatusForDriver'])->middleware('auth:sanctum');

//update order status by driver by id
Route::put('/order/driver/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updateOrderStatusDriver'])->middleware('auth:sanctum');

//update purchase status by id
Route::put('/order/user/update-status/{id}', [App\Http\Controllers\Api\OrderController::class, 'updatePurchaseStatus'])->middleware('auth:sanctum');
