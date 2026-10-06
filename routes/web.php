<?php

use App\Http\Controllers\EchangeController;
use App\Http\Controllers\FactureController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/echanges');
Route::resource('echanges', EchangeController::class);
Route::resource('factures', FactureController::class);