<?php

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

require __DIR__.'/auth.php';

// Admin Only Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show', 'destroy']);
});

// Inventory Management Routes (Admin, Manager, Staff)
Route::middleware(['auth', 'role:admin,manager,staff'])->group(function () {
    Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['show', 'destroy']);
    Route::post('stock/adjust', [\App\Http\Controllers\StockController::class, 'adjust'])->name('stock.adjust');
});

// Settings & Config Routes (Admin, Manager)
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class)->except(['show', 'destroy']);
});
