<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SellerController;
use App\Http\Controllers\Admin\WhatsAppMessageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('sellers', [SellerController::class, 'index'])
        ->middleware('permission:seller.view')
        ->name('sellers.index');

    Route::get('sellers/create', [SellerController::class, 'create'])
        ->middleware('permission:seller.create')
        ->name('sellers.create');

    Route::post('sellers', [SellerController::class, 'store'])
        ->middleware('permission:seller.create')
        ->name('sellers.store');

    Route::get('sellers/{seller}/edit', [SellerController::class, 'edit'])
        ->middleware('permission:seller.edit')
        ->name('sellers.edit');

    Route::put('sellers/{seller}', [SellerController::class, 'update'])
        ->middleware('permission:seller.edit')
        ->name('sellers.update');

    Route::delete('sellers/{seller}', [SellerController::class, 'destroy'])
        ->middleware('permission:seller.delete')
        ->name('sellers.destroy');
    Route::resource('items', ItemController::class);

    // Custom sales routes (must be defined BEFORE resource)
    Route::get('sales/export', [SaleController::class, 'exportYearlySales'])
        ->name('sales.export');
    Route::post('sales/send-report', [SaleController::class, 'sendManualReport'])
        ->name('sales.send-report');

    // WhatsApp Message Routes
    Route::get('whatsapp/send-message', [WhatsAppMessageController::class, 'index'])
        ->name('whatsapp.send-message');
    Route::post('whatsapp/send-message', [WhatsAppMessageController::class, 'send'])
        ->name('whatsapp.send');

    // Resource routes
    Route::resource('sales', SaleController::class);

    // Group-specific routes
    Route::get('sales/{seller}/{date}/edit',
        [SaleController::class, 'editGroup'])
        ->name('sales.edit.group');

    Route::put('sales/{seller}/{date}',
        [SaleController::class, 'updateGroup'])
        ->name('sales.update.group');

});

require __DIR__.'/auth.php';
