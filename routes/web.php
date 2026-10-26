<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FoodOrderController;
use App\Http\Controllers\RiderController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Food Delivery App Order Dispatch System',
    ]);
});

Route::get('/food-orders', [FoodOrderController::class, 'index']);
Route::get('/food-orders/create', [FoodOrderController::class, 'create']);
Route::post('/food-orders', [FoodOrderController::class, 'store']);
Route::put('/food-orders/{food_order}', [FoodOrderController::class, 'update']);

Route::get('/riders', [RiderController::class, 'index']);
Route::get('/riders/create', [RiderController::class, 'create']);
Route::post('/riders', [RiderController::class, 'store']);
Route::put('/riders/{rider}', [RiderController::class, 'update']);