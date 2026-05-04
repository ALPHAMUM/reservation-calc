<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [ReservationController::class, 'index'])->name('dashboard');
Route::get('/export', [ReservationController::class, 'export'])->name('export');
Route::get('/print', [ReservationController::class, 'print'])->name('print');
