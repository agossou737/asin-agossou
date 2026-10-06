<?php

use App\Http\Controllers\Api\DemandeActeController;
use Illuminate\Support\Facades\Route;

// Prefixe automatique : /api

// --- Public : usagers ----------------------------------------------------------
Route::post('demandes', [DemandeActeController::class, 'store'])
    ->middleware('throttle:depot')->name('api.demandes.store');

// Suivi par NPI (toutes les demandes de l'usager) ou par numero de demande (une demande).
Route::get('usagers/{npi}/demandes', [DemandeActeController::class, 'indexParUsager'])
    ->middleware('throttle:consultation')->name('api.usagers.demandes');

Route::get('suivi/{numero}', [DemandeActeController::class, 'suivi'])
    ->middleware('throttle:consultation')->name('api.suivi');

Route::get('suivi/{numero}/pdf', [DemandeActeController::class, 'pdf'])
    ->middleware('throttle:consultation')->name('api.suivi.pdf');

// --- Administration : jeton Bearer obligatoire -----------------------------------
Route::middleware(['throttle:admin-api', 'token.admin'])->group(function () {
    Route::get('demandes/{id}', [DemandeActeController::class, 'show'])->whereUuid('id')->name('api.demandes.show');
    Route::patch('demandes/{id}/statut', [DemandeActeController::class, 'changerStatut'])->whereUuid('id')->name('api.demandes.statut');
});
