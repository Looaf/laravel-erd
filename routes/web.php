<?php

use Illuminate\Support\Facades\Route;
use Looaf\LaravelErd\Http\Controllers\ErdController;

/*
|--------------------------------------------------------------------------
| ERD Package Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the ErdServiceProvider and provide access
| to the ERD interface and data endpoints.
|
*/

Route::get('/', [ErdController::class, 'index'])->name('index');
Route::get('/data', [ErdController::class, 'data'])->name('data');
Route::post('/refresh', [ErdController::class, 'refresh'])->name('refresh');