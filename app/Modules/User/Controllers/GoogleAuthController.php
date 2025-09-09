<?php

namespace App\Modules\User\Controllers;

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
                'error' => 'Código inválido',
            ], 400);
        }

        try {
            // Processa o callback e salva o usuário temporário com o token
            $user = $this->service->handleCallback($code);

            // Recupera a URL de redirecionamento do front-end
            $urlFront = env('FRONT_CALLBACK_URL');

            if (!is_string($urlFront)) {
                throw new \RuntimeException('Variável de ambiente FRONT_CALLBACK_URL inválida.');
            }

            // Monta a URL final com o e-mail como parâmetro
            $uri      = '/register?email=' . $user->email;
            $endPoint = $urlFront . $uri;

            // Redireciona o usuário para o front-end
            return redirect($endPoint);
        } catch (Throwable $e) {
            // Loga qualquer erro ocorrido durante a autenticação
            Log::error('[GoogleAuthController@handleCallback] Erro ao autenticar com Google', [
                'exception' => $e->getMessage(),
                'code'      => $code,
            ]);

            // Retorna resposta de erro genérica
            return response()->json([
                'error'   => 'Falha na autenticação com o Google.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
