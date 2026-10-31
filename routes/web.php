<?php

use App\Http\Controllers\EchangeController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\RecommandationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/echanges');
Route::resource('echanges', EchangeController::class);
Route::resource('factures', FactureController::class);
Route::get('/factures/{facture}/pdf', [FactureController::class, 'downloadPdf'])->name('factures.pdf');
Route::get('/recommandations', [RecommandationController::class, 'index'])->name('recommandations.index');
Route::post('/recommandations', [RecommandationController::class, 'recommend'])->name('recommandations.recommend');
