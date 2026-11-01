<?php

use App\Http\Controllers\CommunauteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EchangeController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Support\Facades\Route;

/*
 * Connexion, inscription, déconnexion, mot de passe oublié, vérification d'e-mail,
 * double authentification et confirmation du mot de passe : routes fournies par Laravel Fortify
 * (vues déclarées dans App\Providers\FortifyServiceProvider).
 */

// Connecté, mais e-mail pas forcément vérifié : profil (pour corriger son e-mail) et photos
Route::middleware('auth')->group(function () {
    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('profil/confirmer', [ProfileController::class, 'confirm'])->name('profile.confirm');
    Route::delete('profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('users/{user}/photo', UserPhotoController::class)->name('users.photo');
});

// Connecté avec un e-mail vérifié
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('communautes', CommunauteController::class);
    Route::resource('echanges', EchangeController::class);
    Route::resource('factures', FactureController::class);
    Route::resource('installations', InstallationController::class);
    Route::resource('productions', ProductionController::class);
    Route::resource('offres', OffreController::class);

    // Réservé aux administrateurs : permission Spatie + UserPolicy (double contrôle côté serveur)
    Route::resource('users', UserController::class)->middleware('permission:gerer-utilisateurs');
});
