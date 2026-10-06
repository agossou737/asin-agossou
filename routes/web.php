<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemandeController;
use App\Http\Controllers\Admin\DemandePageController;
use App\Http\Controllers\Admin\DemandePdfController;
use Illuminate\Support\Facades\Route;

// --- Public --------------------------------------------------------------------
Route::redirect('/', '/demandes/nouvelle');
Route::view('/demandes/nouvelle', 'demandes.nouvelle')->name('demandes.nouvelle');
Route::view('/demandes', 'demandes.index')->name('demandes.index');

// --- Espace administrateur -----------------------------------------------------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('connexion', [AuthController::class, 'form'])->name('login');
        Route::post('connexion', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('deconnexion', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::view('demandes', 'admin.demandes')->name('demandes');
        Route::get('demandes/{id}', DemandePageController::class)->whereUuid('id')->name('demandes.show');
        Route::get('demandes/{id}/pdf', DemandePdfController::class)->whereUuid('id')->name('demandes.pdf');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('demandes', [DemandeController::class, 'index'])->name('demandes.index');
            Route::get('demandes/{id}', [DemandeController::class, 'show'])->whereUuid('id')->name('demandes.show');
            Route::patch('demandes/{id}/statut', [DemandeController::class, 'changerStatut'])->whereUuid('id')->name('demandes.statut');
        });
    });
});
