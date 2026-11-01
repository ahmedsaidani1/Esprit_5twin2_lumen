<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunauteController;
use App\Http\Controllers\Api\CompteurController;
use App\Http\Controllers\Api\ConsommationController;
use App\Http\Controllers\Api\EchangeController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\InstallationController;
use App\Http\Controllers\Api\OffreController;
use App\Http\Controllers\Api\ProductionController;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Préfixe /api ajouté automatiquement. Toutes les réponses sont en JSON.
// Les noms de routes sont préfixés "api." pour ne pas entrer en conflit avec ceux de l'interface web.
Route::name('api.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');

    // Tout le reste exige un jeton : "Authorization: Bearer <token>"
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('stats', fn (Request $request, StatsService $stats) => response()->json($stats->summary($request->user())))->name('stats');
        // Les e-mails ne sont visibles que par les administrateurs ; les membres n'ont besoin que des noms.
        Route::get('users', fn (Request $request) => response()->json(
            $request->user()->isAdmin()
                ? User::with('roles')->orderBy('name')->get()->map(fn ($u) => $u->only('id', 'name', 'email') + ['role' => $u->role])
                : User::select('id', 'name')->orderBy('name')->get()
        ))->name('users');

        Route::apiResources([
            'communautes'   => CommunauteController::class,
            'installations' => InstallationController::class,
            'productions'   => ProductionController::class,
            'offres'        => OffreController::class,
            'echanges'      => EchangeController::class,
            'factures'      => FactureController::class,
            'compteurs'     => CompteurController::class,
            'consommations' => ConsommationController::class,
        ]);
    });
});
