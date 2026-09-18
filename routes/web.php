<?php

use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
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
});

require __DIR__.'/auth.php';
