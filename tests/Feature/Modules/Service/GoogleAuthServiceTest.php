<?php

namespace Tests\Feature\Modules\Service;

use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Services\GoogleAuthService;
use Google_Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o método handleCallback do serviço de autenticação com Google,
     * garantindo que um usuário temporário seja criado corretamente
     * a partir do token de acesso retornado pela API.
     */
    public function testHandleCallbackCreatesTemporaryUser(): void
    {
        $email       = 'test@example.com';
        $accessToken = 'mocked-access-token';
        $googleId    = 'google-id-123';

        $googleUser = (object) [
            'email' => $email,
            'id'    => $googleId,
        ];

        /** @var Google_Client&\Mockery\MockInterface $client */
        $client = Mockery::mock(Google_Client::class);
        $client->allows([  // PHPStan-safe mock setup
            'fetchAccessTokenWithAuthCode' => ['access_token' => $accessToken],
            'setAccessToken'               => null,
            'isAccessTokenExpired'         => false,
            'getLogger'                    => new class () {
                public function info(): void
                {
                }
            },
            'getUniverseDomain' => 'googleapis.com',
            'shouldDefer'       => false,
            'execute'           => (object) [
                'email' => $email,
                'id'    => $googleId,
            ],
        ]);

        /** @var GoogleAuthService&\Mockery\MockInterface $service */
        $service = Mockery::mock(GoogleAuthService::class)->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->allows(['makeClient' => $client]);

        TemporaryUser::where('email', $email)->delete();

        $result = $service->handleCallback('mocked-code');

        $this->assertInstanceOf(TemporaryUser::class, $result);
        $this->assertEquals($email, $result->email);
    }
}
