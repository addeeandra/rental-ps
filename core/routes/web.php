<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // redirect to login
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'overview'])->middleware(['auth', 'verified'])->name('dashboard');
Route::get('dashboard/financial', [DashboardController::class, 'financial'])->middleware(['auth', 'verified'])->name('dashboard.financial');
Route::get('dashboard/operations', [DashboardController::class, 'operations'])->middleware(['auth', 'verified'])->name('dashboard.operations');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect old partners route to customers
    Route::get('partners', fn () => redirect()->route('customers.index'));

    Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
    Route::get('customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
    Route::post('customers/import', [CustomerController::class, 'import'])->name('customers.import');

    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    Route::get('suppliers/template', [SupplierController::class, 'downloadTemplate'])->name('suppliers.template');
    Route::post('suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');

    Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
    Route::get('categories/template', [CategoryController::class, 'downloadTemplate'])->name('categories.template');
    Route::post('categories/import', [CategoryController::class, 'import'])->name('categories.import');

    Route::resource('products', ProductController::class)->except(['show', 'create', 'edit']);
    Route::get('products/template', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');

    Route::resource('warehouses', WarehouseController::class)->except(['show', 'create', 'edit']);
    Route::resource('inventory-items', InventoryItemController::class)->except(['show', 'create', 'edit']);
    Route::resource('stock-movements', StockMovementController::class)->only(['index', 'store']);
    Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');

    Route::resource('invoices', InvoiceController::class)->except(['show', 'create']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('invoices/{invoice}/payment', [InvoiceController::class, 'updatePayment'])->name('invoices.payment');
    Route::patch('invoices/{invoice}/components/{component}/share', [InvoiceController::class, 'updateComponentShare'])->name('invoices.components.share');
    Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('invoices/{invoice}/preview-html', [InvoiceController::class, 'previewHtml'])->name('invoices.preview-html');
});

require __DIR__.'/settings.php';
