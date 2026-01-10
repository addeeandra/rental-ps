<?php

use App\Http\Controllers\PartnerController;
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
});

require __DIR__.'/settings.php';
