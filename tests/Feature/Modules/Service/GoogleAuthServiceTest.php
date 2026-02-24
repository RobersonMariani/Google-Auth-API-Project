<?php

namespace Tests\Feature\Modules\Service;

use App\Modules\User\Enums\AuthMessagesEnum;
use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Services\GoogleAuthService;
use Google_Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class GoogleAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createMockedService(string $email, string $googleId, string $accessToken, bool $expired = false, bool $invalidToken = false): GoogleAuthService
    {
        /** @var Google_Client&\Mockery\MockInterface $client */
        $client = Mockery::mock(Google_Client::class);
        $client->allows([
            'fetchAccessTokenWithAuthCode' => $invalidToken
                ? ['error' => 'invalid_grant']
                : ['access_token' => $accessToken],
            'setAccessToken'               => null,
            'isAccessTokenExpired'         => $expired,
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

        return $service;
    }

    public function testHandleCallbackCreatesTemporaryUser(): void
    {
        // Arrange
        $email       = 'new@example.com';
        $accessToken = 'mocked-access-token';
        $googleId    = 'google-id-123';
        $service     = $this->createMockedService($email, $googleId, $accessToken);

        // Act
        $result = $service->handleCallback('mocked-code');

        // Assert
        $this->assertInstanceOf(TemporaryUser::class, $result);
        $this->assertEquals($email, $result->email);
        $this->assertNotNull($result->expires_at);
        $this->assertDatabaseHas('temporary_users', ['email' => $email]);
    }

    public function testHandleCallbackUpdatesExistingTemporaryUser(): void
    {
        // Arrange
        $email       = 'existing@example.com';
        $accessToken = 'new-access-token';
        $googleId    = 'google-id-456';

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('old-id'),
            'google_token' => Crypt::encryptString('old-token'),
            'expires_at'   => now()->subMinutes(5),
        ]);

        $service = $this->createMockedService($email, $googleId, $accessToken);

        // Act
        $result = $service->handleCallback('mocked-code');

        // Assert
        $this->assertEquals($email, $result->email);
        $this->assertTrue($result->expires_at->isFuture());
        $this->assertDatabaseCount('temporary_users', 1);
    }

    public function testHandleCallbackSetsExpirationTime(): void
    {
        // Arrange
        $email   = 'expiry@example.com';
        $service = $this->createMockedService($email, 'gid', 'token');

        // Act
        $result = $service->handleCallback('code');

        // Assert
        $this->assertNotNull($result->expires_at);
        $expectedMinutes = TemporaryUser::EXPIRATION_MINUTES;
        $this->assertTrue(
            $result->expires_at->between(now()->addMinutes($expectedMinutes - 1), now()->addMinutes($expectedMinutes + 1))
        );
    }

    public function testHandleCallbackWithInvalidTokenThrowsException(): void
    {
        // Arrange
        $service = $this->createMockedService('test@test.com', 'gid', 'token', invalidToken: true);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(AuthMessagesEnum::TOKEN_INVALID->value);

        // Act
        $service->handleCallback('invalid-code');
    }

    public function testHandleCallbackWithExpiredTokenThrowsException(): void
    {
        // Arrange
        $service = $this->createMockedService('test@test.com', 'gid', 'token', expired: true);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(AuthMessagesEnum::TOKEN_EXPIRED->value);

        // Act
        $service->handleCallback('expired-code');
    }
}
