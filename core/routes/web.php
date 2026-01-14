<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirect old partners route to customers
    Route::get('partners', fn() => redirect()->route('customers.index'));
    
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

    Route::resource('invoices', InvoiceController::class)->except(['show', 'create']);
    Route::patch('invoices/{invoice}/payment', [InvoiceController::class, 'updatePayment'])->name('invoices.payment');
    Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
    Route::get('invoices/{invoice}/preview-html', [InvoiceController::class, 'previewHtml'])->name('invoices.preview-html');
});

require __DIR__.'/settings.php';
