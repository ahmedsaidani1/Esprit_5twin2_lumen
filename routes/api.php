<?php

use App\Http\Controllers\Api\CommunauteController;
use App\Http\Controllers\Api\EchangeController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\InstallationController;
use App\Http\Controllers\Api\OffreController;
use App\Http\Controllers\Api\ProductionController;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Support\Facades\Route;

// Préfixe /api ajouté automatiquement. Toutes les réponses sont en JSON.
// Les noms de routes sont préfixés "api." pour ne pas entrer en conflit avec ceux de l'interface web.
Route::name('api.')->group(function () {
    Route::get('stats', fn (StatsService $stats) => response()->json($stats->summary()))->name('stats');
    Route::get('users', fn () => response()->json(User::select('id', 'name', 'email')->orderBy('name')->get()))->name('users');

    Route::apiResources([
        'communautes'   => CommunauteController::class,
        'installations' => InstallationController::class,
        'productions'   => ProductionController::class,
        'offres'        => OffreController::class,
        'echanges'      => EchangeController::class,
        'factures'      => FactureController::class,
    ]);
});
