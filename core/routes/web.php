<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ProductController;
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
    Route::resource('partners', PartnerController::class)->except(['show', 'create', 'edit']);
    Route::get('partners/template', [PartnerController::class, 'downloadTemplate'])->name('partners.template');
    Route::post('partners/import', [PartnerController::class, 'import'])->name('partners.import');

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
