<?php

use App\Http\Controllers\LabelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PackinglistController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderlistController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\BaleController;
use App\Http\Controllers\CancelController;
use App\Http\Controllers\RepackingController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\PlantTransferController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', 
    [DashboardController::class, 'index']
)->middleware(['auth'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/labels', LabelController::class);
    Route::resource('/customers', CustomerController::class);
    Route::resource('/categories', CategoryController::class)->except(['create', 'show']);
    Route::resource('/subcategories', SubcategoryController::class)->except(['create', 'show']);
    Route::resource('/products', ProductController::class);
    Route::resource('/orders', OrderController::class);
    Route::resource('/employees', EmployeeController::class)->except(['create', 'show']);
    Route::resource('/plants', PlantController::class)->except(['create', 'edit', 'show', 'update']);

    Route::get('/products/check-shortcode', [ProductController::class, 'checkShortCode'])->name('products.check-shortcode');
    Route::post('/products/{product}/merge', [ProductController::class, 'merge'])->name('products.merge');

    Route::get('/packinglists', [PackinglistController::class, 'index'])->name('packinglists.index');
    Route::get('/packinglists/{customer}', [PackinglistController::class, 'show'])->name('packinglists.show');
    Route::patch('/packinglists/{packinglist}', [PackinglistController::class, 'update'])->name('packinglists.update');
    Route::post('/packinglists/bulk-update', [PackinglistController::class, 'bulkUpdate'])->name('packinglists.bulk-update');

    Route::post('/orderlists/bulk-update', [OrderlistController::class, 'bulkUpdate'])
        ->name('orderlists.bulk-update');

    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::get('/production/orderlists', [ProductionController::class, 'getOrderlists'])->name('production.orderlists');
    Route::post('/production/bales', [ProductionController::class, 'createBale'])->name('production.bales');

    Route::get('/bales', [BaleController::class, 'index'])->name('bales.index');
    Route::delete('/bales/{bale}', [BaleController::class, 'destroy'])->name('bales.destroy');

    Route::get('/cancellations', [CancelController::class, 'index'])->name('cancellations.index');

    Route::get('/repacking', [RepackingController::class, 'index'])->name('repacking.index');
    Route::get('/repacking/bale-details', [RepackingController::class, 'getBaleDetails']);
    Route::get('/repacking/packinglists', [RepackingController::class, 'getPackinglists']);
    Route::post('/repacking/create-bale', [RepackingController::class, 'createBale']);
    Route::get('/repacking/print-bale/{id}', [RepackingController::class, 'printBale']);

    Route::get('/plant-transfer', [PlantTransferController::class, 'index'])->name('plant-transfer.index');
    Route::get('/plant-transfer/packinglists', [PlantTransferController::class, 'getPackinglists']);
    Route::post('/plant-transfer', [PlantTransferController::class, 'store']);

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

require __DIR__.'/auth.php';
