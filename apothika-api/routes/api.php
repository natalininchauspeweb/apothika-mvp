<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\PatientPointsController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\PharmacyPointsController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\TriviaController;
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

            Route::get('/rewards', [RewardController::class, 'indexForPatient']);
            Route::post('/rewards/{reward}/redeem', [CouponController::class, 'redeem']);
            Route::get('/coupons', [CouponController::class, 'indexForPatient']);

            Route::get('/trivias', [TriviaController::class, 'index']);
            Route::post('/trivias/{trivia}/answer', [TriviaController::class, 'answer']);
        });

    // Zona exclusiva Farmacia (B2B)
    Route::prefix('pharmacy')
        ->middleware('role:pharmacy')
        ->group(function () {
            Route::get('/dashboard', [PharmacyPointsController::class, 'dashboard']);
            Route::get('/patients/lookup', [PharmacyPointsController::class, 'lookupPatient']);
            Route::post('/points/award', [PharmacyPointsController::class, 'awardPurchasePoints']);

            // CRUD de beneficios (POST /rewards/{reward} soporta multipart con imagen)
            Route::get('/rewards', [RewardController::class, 'indexForPharmacy']);
            Route::post('/rewards', [RewardController::class, 'store']);
            Route::match(['put', 'patch', 'post'], '/rewards/{reward}', [RewardController::class, 'update']);
            Route::patch('/rewards/{reward}/toggle', [RewardController::class, 'toggleActive']);
            Route::delete('/rewards/{reward}', [RewardController::class, 'destroy']);

            // Validador de cupones en caja
            Route::post('/coupons/validate', [CouponController::class, 'validateInCounter']);

            // Personalización White-Label
            Route::post('/branding', [PharmacyController::class, 'updateBranding']);
        });
});