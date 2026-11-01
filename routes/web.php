<?php

use App\Http\Controllers\AnalyseConsommationController;
use App\Http\Controllers\CommunauteController;
use App\Http\Controllers\CompteurController;
use App\Http\Controllers\ConsommationController;
use App\Http\Controllers\ConsommationCsvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EchangeController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\IaController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductionCsvController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Support\Facades\Route;

/*
 * Connexion, inscription, déconnexion, mot de passe oublié, vérification d'e-mail,
 * double authentification et confirmation du mot de passe : routes fournies par Laravel Fortify
 * (vues déclarées dans App\Providers\FortifyServiceProvider).
 */

// Supervision (Prometheus) : protégée par un jeton, voir MetricsController
Route::get('metrics', MetricsController::class)->name('metrics');

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

    // Notifications (membre A)
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{id}', [NotificationController::class, 'lire'])->name('notifications.lire');
    Route::post('notifications/tout-lire', [NotificationController::class, 'toutLire'])->name('notifications.tout-lire');

    // Exports CSV (déclarés avant les ressources pour ne pas être pris pour un {id})
    Route::get('productions/export', [ProductionCsvController::class, 'exportProductions'])->name('productions.export');
    Route::get('offres/export', [ProductionCsvController::class, 'exportOffres'])->name('offres.export');
    Route::get('consommations/export', [ConsommationCsvController::class, 'export'])->name('consommations.export');

    Route::resource('communautes', CommunauteController::class);
    Route::resource('echanges', EchangeController::class);
    Route::resource('factures', FactureController::class);
    Route::resource('installations', InstallationController::class);
    Route::resource('productions', ProductionController::class);
    Route::resource('offres', OffreController::class);
    Route::resource('compteurs', CompteurController::class);
    Route::resource('consommations', ConsommationController::class);

    // Membre A : facture PDF et envoi par e-mail
    Route::get('factures/{facture}/pdf', [FactureController::class, 'pdf'])->name('factures.pdf');
    Route::post('factures/{facture}/envoyer', [FactureController::class, 'envoyer'])->name('factures.envoyer');

    // Membres B et C : imports CSV
    Route::post('installations/{installation}/productions/import', [ProductionCsvController::class, 'import'])->name('installations.productions.import');
    Route::post('compteurs/{compteur}/consommations/import', [ConsommationCsvController::class, 'import'])->name('compteurs.consommations.import');

    // IA (permission Spatie "utiliser-ia")
    Route::middleware('permission:utiliser-ia')->group(function () {
        Route::get('ia', [IaController::class, 'index'])->name('ia.index');
        Route::post('ia/entrainer', [IaController::class, 'entrainer'])->middleware('permission:voir-toutes-les-donnees')->name('ia.entrainer');
        Route::get('ma-consommation', AnalyseConsommationController::class)->name('analyse.consommation');
    });

    // Réservé aux administrateurs : permission Spatie + UserPolicy (double contrôle côté serveur)
    Route::resource('users', UserController::class)->middleware('permission:gerer-utilisateurs');
});
