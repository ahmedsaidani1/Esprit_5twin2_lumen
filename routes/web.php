<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunauteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EchangeController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Visiteurs : connexion et inscription
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Utilisateurs connectés
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('communautes', CommunauteController::class);
    Route::resource('echanges', EchangeController::class);
    Route::resource('factures', FactureController::class);
    Route::resource('installations', InstallationController::class);
    Route::resource('productions', ProductionController::class);
    Route::resource('offres', OffreController::class);

    // Réservé aux administrateurs (contrôlé par UserPolicy)
    Route::resource('users', UserController::class);
});
