<?php

namespace Tests\Feature\Modules\Controller;

use App\Modules\User\Enums\AuthMessagesEnum;
use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Services\GoogleAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testLoginUrlReturnsValidUrl(): void
    {
        // Arrange
        $this->partialMock(GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('generateLoginUrl')
                ->andReturn('https://accounts.google.com/o/oauth2/v2/auth?fake');
        });

        // Act
        $response = $this->getJson('/api/google/login-url');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure(['url'])
            ->assertJsonFragment(['url' => 'https://accounts.google.com/o/oauth2/v2/auth?fake']);
    }

    public function testCallbackStoresTemporaryUserAndRedirects(): void
    {
        // Arrange
        $email    = 'callback@test.com';
        $token    = 'mocked-token';
        $googleId = 'mock-id';

        $this->partialMock(GoogleAuthService::class, function ($mock) use ($email, $googleId, $token) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('handleCallback')->andReturn(
                TemporaryUser::create([
                    'email'        => $email,
                    'google_id'    => Crypt::encryptString($googleId),
                    'google_token' => Crypt::encryptString($token),
                    'expires_at'   => now()->addMinutes(15),
                ])
            );
        });

        // Act
        $response = $this->get('/api/google/callback?code=valid-code');

        // Assert
        $response->assertRedirectContains('?email=' . urlencode($email));
        $this->assertDatabaseHas('temporary_users', ['email' => $email]);
    }

    public function testCallbackWithoutCodeReturns400(): void
    {
        // Arrange — nenhum código na query string

        // Act
        $response = $this->getJson('/api/google/callback');

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment(['error' => 'Código inválido']);
    }

    public function testCallbackWithEmptyCodeReturns400(): void
    {
        // Arrange
        $code = '';

        // Act
        $response = $this->getJson('/api/google/callback?code=' . $code);

        // Assert
        $response->assertStatus(400)
            ->assertJsonFragment(['error' => 'Código inválido']);
    }

    public function testCallbackWithServiceErrorReturnsGenericMessage(): void
    {
        // Arrange
        $this->partialMock(GoogleAuthService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('handleCallback')
                ->andThrow(new \Exception('Internal sensitive error details'));
        });

        // Act
        $response = $this->getJson('/api/google/callback?code=invalid-code');

        // Assert
        $response->assertStatus(500)
            ->assertJsonFragment(['error' => AuthMessagesEnum::INTEGRATION_ERROR->value])
            ->assertJsonMissing(['message' => 'Internal sensitive error details']);
    }
}
