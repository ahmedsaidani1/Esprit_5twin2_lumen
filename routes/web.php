<?php

use App\Http\Controllers\CommunauteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EchangeController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\InstallationController;
use App\Http\Controllers\OffreController;
use App\Http\Controllers\ProductionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::resource('communautes', CommunauteController::class);
Route::resource('echanges', EchangeController::class);
Route::resource('factures', FactureController::class);
Route::resource('installations', InstallationController::class);
Route::resource('productions', ProductionController::class);
Route::resource('offres', OffreController::class);
