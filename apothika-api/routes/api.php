<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientPointsController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\PharmacyPointsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (sin token)
|--------------------------------------------------------------------------
*/
Route::get('/pharmacies/{slug}/branding', [PharmacyController::class, 'showBranding']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (requieren Bearer Token válido de Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Zona exclusiva Paciente (B2C)
    Route::prefix('patient')
        ->middleware('role:patient')
        ->group(function () {
            Route::get('/points', [PatientPointsController::class, 'summary']);
        });

    // Zona exclusiva Farmacia (B2B)
    Route::prefix('pharmacy')
        ->middleware('role:pharmacy')
        ->group(function () {
            Route::get('/dashboard', [PharmacyPointsController::class, 'dashboard']);
            Route::get('/patients/lookup', [PharmacyPointsController::class, 'lookupPatient']);
            Route::post('/points/award', [PharmacyPointsController::class, 'awardPurchasePoints']);
        });
});