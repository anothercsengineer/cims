<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
    Route::resource('categories', \App\Http\Controllers\CategoryController::class)->except(['show', 'destroy']);
    Route::post('products/import', [\App\Http\Controllers\ProductController::class, 'import'])->name('products.import');
    Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['show', 'destroy']);
    
    // Stock Mechanics
    Route::post('stock/adjust', [\App\Http\Controllers\StockController::class, 'adjust'])->name('stock.adjust');
    Route::get('stock/transfer', [\App\Http\Controllers\StockController::class, 'showTransferForm'])->name('stock.transfer');
    Route::post('stock/transfer', [\App\Http\Controllers\StockController::class, 'executeTransfer'])->name('stock.transfer.execute');
    
    // Purchasing
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class)->except(['show', 'destroy']);
    Route::resource('purchase-orders', \App\Http\Controllers\PurchaseOrderController::class)->except(['edit', 'update', 'destroy']);
    Route::post('purchase-orders/{purchase_order}/receive', [\App\Http\Controllers\PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');

    // Sales
    Route::resource('customers', \App\Http\Controllers\CustomerController::class)->except(['show', 'destroy']);
    Route::resource('sales-orders', \App\Http\Controllers\SalesOrderController::class)->except(['edit', 'update', 'destroy']);
    Route::post('sales-orders/{sales_order}/fulfill', [\App\Http\Controllers\SalesOrderController::class, 'fulfill'])->name('sales-orders.fulfill');

    // Reports & Exports
    Route::get('reports/inventory', [\App\Http\Controllers\ReportController::class, 'exportInventory'])->name('reports.inventory');
    Route::get('reports/ledger', [\App\Http\Controllers\ReportController::class, 'exportLedger'])->name('reports.ledger');
});

// Settings & Config Routes (Admin, Manager)
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::resource('warehouses', \App\Http\Controllers\WarehouseController::class)->except(['show', 'destroy']);
});
