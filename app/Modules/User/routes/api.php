<?php

use App\Modules\User\Controllers\GoogleAuthController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas relacionadas aos usuários do sistema.
 */
Route::prefix('users')->group(function () {
    // Lista usuários com filtros opcionais
    Route::get('/', [UserController::class, 'index']);

    // Finaliza o cadastro de um usuário após login via Google
    Route::post('/complete', [UserController::class, 'complete']);
});

/**
 * Rotas para autenticação com o Google (OAuth).
 */
Route::prefix('google')->group(function () {
    // Retorna a URL de login do Google
    Route::get('/login-url', [GoogleAuthController::class, 'getLoginUrl']);

    // Manipula o callback do Google após autenticação
    Route::get('/callback', [GoogleAuthController::class, 'handleCallback']);
});
