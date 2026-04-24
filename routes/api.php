<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\SellerController;
use App\Http\Controllers\API\AnalyticsController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'getProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/red-flags', [DashboardController::class, 'getRedFlagSales']);
    Route::get('/dashboard/entry-days', [DashboardController::class, 'getEntryDays']);
    Route::get('/dashboard/seller-performance', [DashboardController::class, 'getSellerPerformance']);

    // Analytics endpoints
    Route::prefix('analytics')->group(function () {
        Route::get('/monthly-sales', [AnalyticsController::class, 'getMonthlySales']);
        Route::get('/daily-sales', [AnalyticsController::class, 'getDailySales']);
        Route::get('/top-sellers', [AnalyticsController::class, 'getTopSellers']);
        Route::get('/avg-sellers', [AnalyticsController::class, 'getTopSellersByAvgSale']);
        Route::get('/items', [AnalyticsController::class, 'getItemPopularity']);
        Route::get('/summary', [AnalyticsController::class, 'getDashboardSummary']);
        Route::get('/day-of-week', [AnalyticsController::class, 'getSalesByDayOfWeek']);
    });

    Route::apiResource('sellers', SellerController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('sales', SaleController::class);
    Route::get('/sellers/{sellerId}/sales', [SaleController::class, 'showSellerSales']);
});

