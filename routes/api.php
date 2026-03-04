<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SellerController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('sellers', SellerController::class);
});

Route::post('/login', [AuthController::class, 'login']);
