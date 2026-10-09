<?php

use App\Http\Controllers\Alerts\AlertController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Audit\AuditLogController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\CashController;
use App\Http\Controllers\Finance\CashDeclarationController;
use App\Http\Controllers\Finance\ExchangeController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\Rentals\RentalController;
use App\Http\Controllers\Requests\CustomerRequestController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Savings\SavingsController;
use App\Http\Controllers\Stock\ProductStockController;
use App\Http\Controllers\Stock\StockLocationController;
use App\Http\Controllers\Stock\StockMovementController;
use App\Http\Controllers\Supply\ArrivalController;
use App\Http\Controllers\Users\InvitationController;
use App\Http\Controllers\Users\UserSuspensionController;
use App\Services\BootstrapRegistrationService;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (BootstrapRegistrationService $registration) {
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
    Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])->name('profile.photo.store');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');
    Route::get('/utilisateurs/{member}/photo', [ProfilePhotoController::class, 'show'])->name('users.photo');

    Route::middleware('permission:manage_employees')->group(function () {
        Route::get('/utilisateurs', [InvitationController::class, 'index'])->name('users.invitations.index');
        Route::get('/utilisateurs/inviter', [InvitationController::class, 'create'])->name('users.invitations.create');
        Route::post('/utilisateurs/invitations', [InvitationController::class, 'store'])->name('users.invitations.store');
        Route::delete('/utilisateurs/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('users.invitations.destroy');
        Route::post('/utilisateurs/{member}/suspension', [UserSuspensionController::class, 'store'])->name('users.suspend');
        Route::delete('/utilisateurs/{member}/suspension', [UserSuspensionController::class, 'destroy'])->name('users.reactivate');

        Route::get('/maintenance', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
        Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::delete('/maintenance', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
    });

    Route::get('/api/me', MeController::class)->name('api.me');

    Route::middleware('permission:create_sales')->group(function () {
        Route::get('/ventes', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/ventes/nouvelle', [SaleController::class, 'create'])->name('sales.create');
        Route::get('/ventes/recherche', [SaleController::class, 'create'])->name('sales.search');
        Route::post('/ventes', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/ventes/{sale}/facture', [SaleController::class, 'invoice'])->name('sales.invoice');
        Route::get('/ventes/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('/ventes/{sale}/annuler', [SaleController::class, 'cancel'])
            ->middleware('permission:cancel_sales')
            ->name('sales.cancel');
    });

    Route::middleware('permission:create_sales')->group(function () {
        Route::get('/caisse/comptage', [CashDeclarationController::class, 'index'])->name('cash.counts.index');
        Route::post('/caisse/comptage', [CashDeclarationController::class, 'store'])->name('cash.counts.store');
    });

    Route::middleware('permission:manage_expenses')->group(function () {
        Route::get('/caisse', [CashController::class, 'index'])->name('cash.index');
        Route::get('/caisse/change', [ExchangeController::class, 'index'])->name('cash.exchange');
        Route::post('/caisse/taux', [ExchangeController::class, 'storeRate'])->name('cash.rates.store');
        Route::post('/caisse/change', [ExchangeController::class, 'store'])->name('cash.exchange.store');
        Route::post('/caisse/correction', [CashDeclarationController::class, 'adjust'])->name('cash.adjust');
        Route::post('/caisse/comptage/{cashDeclaration}/valider', [CashDeclarationController::class, 'validateDeclaration'])->name('cash.counts.validate');
        Route::post('/caisse/comptage/{cashDeclaration}/corriger', [CashDeclarationController::class, 'correct'])->name('cash.counts.correct');
        Route::get('/depenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/depenses/nouvelle', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/depenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/depenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
        Route::post('/depenses/{expense}/valider', [ExpenseController::class, 'validateExpense'])->name('expenses.validate');
        Route::post('/depenses/{expense}/refuser', [ExpenseController::class, 'refuse'])->name('expenses.refuse');
    });

    Route::middleware('permission:create_customer_requests')->group(function () {
        Route::get('/demandes', [CustomerRequestController::class, 'index'])->name('requests.index');
        Route::get('/demandes/nouvelle', [CustomerRequestController::class, 'create'])->name('requests.create');
        Route::post('/demandes', [CustomerRequestController::class, 'store'])->name('requests.store');
        Route::post('/demandes/suggestions/{restockSuggestion}', [CustomerRequestController::class, 'updateSuggestion'])
            ->middleware('permission:manage_sales')
            ->name('requests.suggestions.update');
        Route::get('/demandes/{customerRequest}', [CustomerRequestController::class, 'show'])->name('requests.show');
        Route::post('/demandes/{customerRequest}/satisfaire', [CustomerRequestController::class, 'fulfill'])->name('requests.fulfill');
        Route::post('/demandes/{customerRequest}/annuler', [CustomerRequestController::class, 'cancel'])->name('requests.cancel');
    });

    Route::middleware(['permission:manage_rentals', 'feature:rentals'])->group(function () {
        Route::get('/locations', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/locations/nouvelle', [RentalController::class, 'create'])->name('rentals.create');
        Route::post('/locations', [RentalController::class, 'store'])->name('rentals.store');
        Route::get('/locations/{rental}', [RentalController::class, 'show'])->name('rentals.show');
        Route::post('/locations/{rental}/cloturer', [RentalController::class, 'close'])->name('rentals.close');
    });

    Route::get('/alertes', [AlertController::class, 'index'])->name('alerts.index');
    Route::middleware('permission:view_reports')->get('/epargne', [SavingsController::class, 'show'])->name('savings.show');
    Route::middleware('permission:view_audit')->get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

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

    Route::middleware('permission:record_stock_receipts')->group(function () {
        Route::get('/arrivages', [ArrivalController::class, 'index'])->name('arrivals.index');
        Route::get('/arrivages/nouveau', [ArrivalController::class, 'create'])->name('arrivals.create');
        Route::post('/arrivages', [ArrivalController::class, 'store'])->name('arrivals.store');
        Route::get('/arrivages/en-attente', [ArrivalController::class, 'pending'])->name('arrivals.pending');
        Route::get('/arrivages/historique', [ArrivalController::class, 'history'])->name('arrivals.history');
        Route::get('/arrivages/{arrival}', [ArrivalController::class, 'show'])->name('arrivals.show');

        Route::middleware('permission:validate_stock_receipts')->group(function () {
            Route::post('/arrivages/{arrival}/valider', [ArrivalController::class, 'approve'])->name('arrivals.approve');
            Route::post('/arrivages/{arrival}/rejeter', [ArrivalController::class, 'reject'])->name('arrivals.reject');
        });
    });
});

require __DIR__.'/auth.php';
