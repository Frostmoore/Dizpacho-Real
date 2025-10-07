<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// DASHBOARD
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// PRICE LIST
Route::middleware(['auth'])->group(function () {
    Route::get('/pricelist', [PriceListController::class, 'index'])
        ->name('pricelist.index');
});

// CUSTOMERS
Route::middleware(['auth'])->group(function () {
    Route::get('/customers', [CustomerController::class, 'index'])
        ->name('customers.index');
});


// PROFILE
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
require __DIR__.'/general.php';
require __DIR__.'/operators.php';
require __DIR__.'/users.php';
