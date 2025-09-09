<?php

namespace App\Modules\User\Services;

use App\Modules\User\Enums\AuthMessagesEnum;
use App\Modules\User\Models\TemporaryUser;
use Google_Client;
use Google_Service_Oauth2;
use Illuminate\Support\Facades\Crypt;

/**
 * Serviço responsável por lidar com a autenticação OAuth via Google.
 */
class GoogleAuthService
{
    /**
     * Gera a URL de login do Google.
     *
     */
    public function generateLoginUrl(): string
    {
        $client = $this->makeClient();
        return $client->createAuthUrl();
    }

    /**
     * Trata o callback do Google após a autenticação do usuário.
     * Cria ou atualiza um registro temporário com os dados do usuário e token.
     *
     * @param string $code Código de autorização retornado pelo Google
     *
     * @throws \Exception Se o token for inválido ou expirado
     */
    public function handleCallback(string $code): TemporaryUser
    {
        $client    = $this->makeClient();
        $tokenData = $client->fetchAccessTokenWithAuthCode($code);

        if (!isset($tokenData['access_token'])) {
            throw new \Exception(AuthMessagesEnum::TOKEN_INVALID->value);
        }

        $client->setAccessToken($tokenData);

        if ($client->isAccessTokenExpired()) {
            throw new \Exception(AuthMessagesEnum::TOKEN_EXPIRED->value);
        }

        $service    = new Google_Service_Oauth2($client);
        $googleUser = $service->userinfo->get();

        $user = TemporaryUser::whereEmail($googleUser->email)->first();

        if ($user) {
            $user->update([
                'google_id'    => Crypt::encryptString($googleUser->id),
                'google_token' => Crypt::encryptString($tokenData['access_token']),
            ]);
            return $user;
        }

        return TemporaryUser::create([
            'email'        => $googleUser->email,
            'google_id'    => Crypt::encryptString($googleUser->id),
            'google_token' => Crypt::encryptString($tokenData['access_token']),
        ]);
    }

    /**
     * Cria e configura uma instância do Google Client.
     *
     */
    protected function makeClient(): Google_Client
    {
        $client = new Google_Client();

        $clientId     = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri  = config('services.google.redirect_uri');

        if (!is_string($clientId) || !is_string($clientSecret) || !is_string($redirectUri)) {
            throw new \RuntimeException('Configuração do Google inválida.');
        }

        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $client->addScope('email');
        $client->addScope('profile');

        return $client;
    }
}
