<?php

use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Stock\ProductStockController;
use App\Http\Controllers\Stock\StockLocationController;
use App\Http\Controllers\Stock\StockMovementController;
use App\Services\BootstrapRegistrationService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (BootstrapRegistrationService $registration) {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => $registration->isOpen(),
    ]);
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/api/me', MeController::class)->name('api.me');

    Route::middleware('permission:search_products')->group(function () {
        Route::get('/articles', [ProductController::class, 'index'])->name('products.index');

        Route::middleware('permission:manage_products')->group(function () {
            Route::get('/articles/nouveau', [ProductController::class, 'create'])->name('products.create');
            Route::post('/articles', [ProductController::class, 'store'])->name('products.store');
        });

        Route::get('/articles/{product}', [ProductController::class, 'show'])->name('products.show');

        Route::middleware('permission:manage_products')->group(function () {
            Route::get('/articles/{product}/modifier', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('/articles/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::post('/articles/{product}/desactiver', [ProductController::class, 'deactivate'])->name('products.deactivate');
            Route::post('/articles/{product}/activer', [ProductController::class, 'activate'])->name('products.activate');
            Route::post('/articles/{product}/stock/entree', [ProductStockController::class, 'receive'])->name('products.stock.receive');
            Route::post('/articles/{product}/stock/transfert', [ProductStockController::class, 'transferToBoutique'])->name('products.stock.transfer');
            Route::post('/articles/{product}/stock/ajustement', [ProductStockController::class, 'adjust'])->name('products.stock.adjust');
        });

        Route::get('/stocks', [StockLocationController::class, 'overview'])->name('stocks.overview');
        Route::get('/stocks/boutique', [StockLocationController::class, 'boutique'])->name('stocks.boutique');
        Route::get('/stocks/depot', [StockLocationController::class, 'depot'])->name('stocks.depot');
        Route::get('/stocks/mouvements', [StockMovementController::class, 'index'])->name('stocks.movements');
    });
});

require __DIR__.'/auth.php';
