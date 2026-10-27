<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\FoodOrderController;
use App\Http\Controllers\RiderController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/food-orders', [FoodOrderController::class, 'index'])
        ->name('food-orders.index');
    Route::get('/food-orders/create', [FoodOrderController::class, 'create'])
        ->name('food-orders.create');
    Route::post('/food-orders', [FoodOrderController::class, 'store'])
        ->name('food-orders.store');
    Route::put('/food-orders/{food_order}', [FoodOrderController::class, 'update'])
        ->name('food-orders.update');

    Route::get('/riders', [RiderController::class, 'index']);
    Route::get('/riders/create', [RiderController::class, 'create']);
    Route::post('/riders', [RiderController::class, 'store']);
    Route::put('/riders/{rider}', [RiderController::class, 'update']);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
