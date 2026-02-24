<?php

namespace App\Modules\User\Controllers;

use App\Modules\User\Enums\AuthMessagesEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controlador responsável por lidar com a autenticação do usuário via Google OAuth.
 */
class GoogleAuthController extends Controller
{
    public function __construct(private GoogleAuthService $service)
    {
    }

    /**
     * Gera a URL de login do Google para iniciar o processo de autenticação.
     *
     * @return JsonResponse Retorna a URL para o front-end redirecionar o usuário.
     */
    public function getLoginUrl(): JsonResponse
    {
        return response()->json([
            'url' => $this->service->generateLoginUrl(),
        ]);
    }

    /**
     * Manipula o callback do Google após o login do usuário.
     * Salva o token e redireciona o usuário para o front-end com o e-mail recuperado.
     *
     * @param Request $request Requisição contendo o código de autorização do Google.
     *
     * @return RedirectResponse|JsonResponse Redireciona para o front-end ou retorna erro em JSON.
     */
    public function handleCallback(Request $request): RedirectResponse|JsonResponse
    {
        // Recupera o código de autorização da query string
        $code = $request->query('code');

        // Verifica se o código é válido
        if (!is_string($code) || empty($code)) {
            return response()->json([
                'error' => AuthMessagesEnum::INVALID_CODE->value,
            ], 400);
        }

        try {
            $tempUser = $this->service->handleCallback($code);

            /** @var string $urlFront */
            $urlFront = config('services.front_callback_url');
            /** @var string $registerPath */
            $registerPath = config('services.front_register_path');

            if (empty($urlFront)) {
                throw new \RuntimeException(AuthMessagesEnum::INVALID_FRONT_CONFIG->value);
            }

            $existingUser = User::where('google_email', $tempUser->email)->first();

            if ($existingUser) {
                $token = $existingUser->createToken('auth_token')->plainTextToken;

                return redirect($urlFront . '/users?token=' . $token);
            }

            $endPoint = $urlFront . $registerPath . '?email=' . urlencode($tempUser->email);

            return redirect($endPoint);
        } catch (Throwable $e) {
            Log::error('[GoogleAuthController@handleCallback] Erro ao autenticar com Google', [
                'exception' => $e->getMessage(),
                'code'      => $code,
            ]);

            return response()->json([
                'error' => AuthMessagesEnum::INTEGRATION_ERROR->value,
            ], 500);
        }
    }
}
