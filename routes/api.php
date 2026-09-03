<?php

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MealController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OwnerApplicationController;
use App\Http\Controllers\Api\V1\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/ping', function () {
        return response()->json([
            'message' => 'Mini talabat api is working',
        ]);
    });

    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    Route::post('/login', [
        AuthController::class,
        'login',
    ])->middleware('throttle:login');

    Route::apiResource('restaurants', RestaurantController::class)
        ->only(['index', 'show']);

    Route::apiResource('restaurants.meals', MealController::class)
        ->only(['index', 'show'])
        ->shallow();

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::post('/owner-applications', [
            OwnerApplicationController::class,
            'store',
        ]);

        Route::get('/owner-applications/{application}', [
            OwnerApplicationController::class,
            'show',
        ]);

        Route::middleware('admin')->group(function () {
            Route::get('/admin/overview', [
                AdminController::class,
                'overview',
            ]);

            Route::get('/admin/owner-applications', [
                OwnerApplicationController::class,
                'index',
            ]);

            Route::post('/admin/owner-applications/{application}/approve', [
                OwnerApplicationController::class,
                'approve',
            ]);

            Route::post('/admin/owner-applications/{application}/reject', [
                OwnerApplicationController::class,
                'reject',
            ]);
        });

        Route::apiResource('restaurants', RestaurantController::class)
            ->only(['store', 'update', 'destroy']);

        Route::apiResource('restaurants.meals', MealController::class)
            ->only(['store', 'update', 'destroy'])
            ->shallow();

        Route::apiResource('orders', OrderController::class);
        Route::apiResource('addresses', AddressController::class);
    });
});
