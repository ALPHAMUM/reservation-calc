<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
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
Route::get('/api/reservations/rates', [ReservationController::class, 'getRates']);
Route::get('/api/status', [ReservationController::class, 'apiStatus'])->name('api.status');

use App\Http\Controllers\SettingsController;
Route::get('/setup', [SettingsController::class, 'index'])->name('setup');
Route::post('/setup', [SettingsController::class, 'update']);
Route::get('/setup/rates/export', [SettingsController::class, 'exportRates'])->name('rates.export');
