<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\ItemController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\SellerController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'getProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/red-flags', [DashboardController::class, 'getRedFlagSales']);
    Route::get('/dashboard/entry-days', [DashboardController::class, 'getEntryDays']);
    Route::get('/dashboard/seller-performance', [DashboardController::class, 'getSellerPerformance']);

    Route::apiResource('sellers', SellerController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('sales', SaleController::class);
    Route::get('/sellers/{sellerId}/sales', [SaleController::class, 'showSellerSales']);
});

