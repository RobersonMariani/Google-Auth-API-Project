<?php

use App\Modules\User\Controllers\GoogleAuthController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas relacionadas aos usuários do sistema.
 */
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])
        ->middleware('auth:sanctum');

    Route::post('/complete', [UserController::class, 'complete'])
        ->middleware('throttle:registration');
});

/**
 * Rotas para autenticação com o Google (OAuth).
 */
Route::prefix('google')->middleware('throttle:auth')->group(function () {
    Route::get('/login-url', [GoogleAuthController::class, 'getLoginUrl']);
    Route::get('/callback', [GoogleAuthController::class, 'handleCallback']);
});
