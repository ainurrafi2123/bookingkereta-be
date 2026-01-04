<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PenumpangController;
use App\Http\Controllers\KeretaController;
use App\Http\Controllers\GerbongController;
use App\Http\Controllers\KursiController;
use App\Http\Controllers\JadwalKeretaController;
use App\Http\Controllers\PembelianTiketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ==========================================
    // AUTH CONTROLLER
    // ==========================================
    Route::controller(AuthController::class)
        ->prefix('auth')
        ->name('api.v1.auth.')
        ->group(function () {
            // Public
            Route::post('/register', 'register')->name('register');
            Route::post('/login', 'login')->name('login');
            
            // Auth Required
            Route::middleware('auth:sanctum')->group(function () {
                Route::post('/logout', 'logout')->name('logout');
            });
        });

    // ==========================================
    // USER CONTROLLER
    // ==========================================
    Route::controller(UserController::class)
        ->prefix('users')
        ->group(function () {
            // Auth Required - Manage Self
            Route::middleware('auth:sanctum')
                ->name('api.v1.users.')
                ->group(function () {
                    Route::get('/me', 'me')->name('me');
                    Route::post('/me', 'updateMe')->name('update-me');
                    Route::delete('/me', 'destroyMe')->name('destroy-me');
                });

            // Petugas Only - Manage All Users
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.users.petugas.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->name('show');
                    Route::post('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });
        });

    // ==========================================
    // PETUGAS CONTROLLER
    // ==========================================
    Route::middleware(['auth:sanctum', 'role:petugas'])
        ->controller(PetugasController::class)
        ->prefix('petugas')
        ->name('api.v1.petugas.petugas.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/me', 'me')->name('me');
            Route::get('/{id}', 'show')->name('show');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
        });

    // ==========================================
    // PENUMPANG CONTROLLER
    // ==========================================
    Route::controller(PenumpangController::class)
        ->prefix('penumpang')
        ->group(function () {
            // Auth Required - Manage Self
            Route::middleware('auth:sanctum')
                ->name('api.v1.penumpang.')
                ->group(function () {
                    Route::get('/me', 'me')->name('me');
                    Route::put('/me', 'updateMe')->name('update-me');
                    Route::delete('/me', 'destroyMe')->name('destroy-me');
                });

            // Petugas Only - Manage All Penumpang
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.penumpang.petugas.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->name('show');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });
        });

    // ==========================================
    // KERETA CONTROLLER
    // ==========================================
    Route::controller(KeretaController::class)
        ->prefix('kereta')
        ->group(function () {
            // Public - Read Only
            Route::name('api.v1.kereta.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->name('show');
                });

            // Petugas Only - CUD Operations
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.kereta.petugas.')
                ->group(function () {
                    Route::post('/', 'store')->name('store');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });
        });

    // ==========================================
    // GERBONG CONTROLLER
    // ==========================================
    Route::controller(GerbongController::class)
        ->prefix('gerbong')
        ->group(function () {
            // Public - Read Only
            Route::name('api.v1.gerbong.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->name('show');
                });

            // Petugas Only - CUD Operations
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.gerbong.petugas.')
                ->group(function () {
                    Route::post('/', 'store')->name('store');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });
        });

    // ==========================================
    // KURSI CONTROLLER
    // ==========================================
    Route::controller(KursiController::class)
        ->group(function () {
            // Public - Read Only
            Route::name('api.v1.kursi.')
                ->group(function () {
                    Route::get('/kursi', 'index')->name('index');
                    Route::get('/kursi/{id}', 'show')->name('show');
                    Route::get('/gerbong/{id}/seat-map', 'getSeatMap')->name('seat-map');
                });

            // Petugas Only - Generate & Reset
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.kursi.petugas.')
                ->group(function () {
                    Route::post('/gerbong/{id}/generate-kursi', 'generateByGerbong')->name('generate');
                    Route::delete('/gerbong/{id}/reset-kursi', 'resetKursi')->name('reset');
                });
        });

    // ==========================================
    // JADWAL KERETA CONTROLLER
    // ==========================================
    Route::controller(JadwalKeretaController::class)
        ->prefix('jadwal-kereta')
        ->group(function () {
            // Public - Read Only
            Route::name('api.v1.jadwal.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->name('show');
                });

            // Petugas Only - CUD Operations
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.jadwal.petugas.')
                ->group(function () {
                    Route::post('/', 'store')->name('store');
                    Route::put('/{id}', 'update')->name('update');
                    Route::delete('/{id}', 'destroy')->name('destroy');
                });
        });

    // ==========================================
    // PEMBELIAN TIKET CONTROLLER
    // ==========================================
    Route::controller(PembelianTiketController::class)
        ->prefix('pembelian-tiket')
        ->group(function () {
            // Auth Required - User Operations
            Route::middleware('auth:sanctum')
                ->name('api.v1.pembelian-tiket.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/', 'store')->name('store');
                    
                    // Custom endpoints - spesifik di atas
                    Route::get('/kode/{kode_tiket}', 'showByKode')->name('show-by-kode');
                    Route::get('/jadwal/{id_jadwal}/kursi-tersedia', 'getAvailableSeats')->name('available-seats');
                    
                    Route::get('/{id}', 'show')->name('show');
                    Route::put('/{id}/cancel', 'cancel')->name('cancel');
                });

            // Petugas Only - Statistics
            Route::middleware(['auth:sanctum', 'role:petugas'])
                ->name('api.v1.pembelian-tiket.petugas.')
                ->group(function () {
                    Route::get('/statistics/data', 'statistics')->name('statistics');
                });
        });

});