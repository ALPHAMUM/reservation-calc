<?php

use App\Http\Controllers\ReservationController;
use App\Http\Controllers\BalesinPinesController;
use App\Http\Controllers\BalesinCityController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [ReservationController::class, 'index'])->name('dashboard');
Route::get('/export', [ReservationController::class, 'export'])->name('export');
Route::get('/print', [ReservationController::class, 'print'])->name('print');
Route::get('/api/reservations/rates', [ReservationController::class, 'getRates']);
Route::get('/api/status', [ReservationController::class, 'apiStatus'])->name('api.status');
Route::get('/member-search', [ReservationController::class, 'memberSearch'])->name('member.search');

Route::get('/pines', [BalesinPinesController::class, 'index'])->name('pines.dashboard');
Route::get('/pines/print', [BalesinPinesController::class, 'print'])->name('pines.print');
Route::get('/pines/export', [BalesinPinesController::class, 'export'])->name('pines.export');

Route::get('/city', [BalesinCityController::class, 'index'])->name('city.dashboard');
Route::get('/city/print', [BalesinCityController::class, 'print'])->name('city.print');
Route::get('/city/export', [BalesinCityController::class, 'export'])->name('city.export');

Route::get('/setup', [SettingsController::class, 'index'])->name('setup');
Route::post('/setup', [SettingsController::class, 'update']);
Route::get('/setup/rates/export', [SettingsController::class, 'exportRates'])->name('rates.export');
