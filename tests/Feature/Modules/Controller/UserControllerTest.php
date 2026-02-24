<?php

namespace Tests\Feature\Modules\Controller;

use App\Modules\User\Enums\UserMessagesEnum;
use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsAuthenticatedUserData(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['name' => 'Roberson', 'cpf' => '12345678900']);
        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Roberson']);
    }

    public function testIndexWithoutAuthReturns401(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Roberson']);

        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(401);
    }

    public function testIndexDoesNotExposeGoogleTokenOrEmail(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create([
            'google_token' => 'secret-token-value',
            'google_email' => 'secret@google.com',
        ]);
        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/users');

        // Assert
        $response->assertStatus(200)
            ->assertJsonMissing(['google_token' => 'secret-token-value'])
            ->assertJsonMissing(['google_email' => 'secret@google.com']);
    }

    public function testCompleteCreatesUserFromTemporary(): void
    {
        // Arrange
        $email    = 'test@example.com';
        $token    = 'mocked-token';
        $googleId = 'google-id-456';

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString($googleId),
            'google_token' => Crypt::encryptString($token),
            'expires_at'   => now()->addMinutes(15),
        ]);

        // Act
        $response = $this->postJson('/api/users/complete', [
            'name'       => 'Novo Usuário',
            'cpf'        => '52998224725',
            'birth_date' => '1999-12-31',
            'email'      => $email,
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonFragment(['message' => UserMessagesEnum::CREATED->value]);
        $this->assertDatabaseHas('users', ['google_email' => $email, 'cpf' => '52998224725']);
    }

    public function testCompleteWithInvalidCpfReturns422(): void
    {
        // Arrange — CPF com dígitos verificadores inválidos

        // Act
        $response = $this->postJson('/api/users/complete', [
            'name'       => 'Teste',
            'cpf'        => '11122233344',
            'birth_date' => '1995-06-15',
            'email'      => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }

    public function testCompleteWithRepeatedDigitsCpfReturns422(): void
    {
        // Arrange — CPF com todos os dígitos iguais

        // Act
        $response = $this->postJson('/api/users/complete', [
            'name'       => 'Teste',
            'cpf'        => '11111111111',
            'birth_date' => '1995-06-15',
            'email'      => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cpf']);
    }

    public function testCompleteWithFutureBirthDateReturns422(): void
    {
        // Arrange
        $futureDate = now()->addYear()->format('Y-m-d');

        // Act
        $response = $this->postJson('/api/users/complete', [
            'name'       => 'Teste',
            'cpf'        => '52998224725',
            'birth_date' => $futureDate,
            'email'      => 'test@example.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date']);
    }

    public function testCompleteWithMissingFieldsReturns422(): void
    {
        // Arrange — payload vazio

        // Act
        $response = $this->postJson('/api/users/complete', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'cpf', 'birth_date', 'email']);
    }

    public function testCompleteResponseUsesResourceFormat(): void
    {
        // Arrange
        $email = 'resource@test.com';
        $token = 'mocked-token';

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('gid'),
            'google_token' => Crypt::encryptString($token),
            'expires_at'   => now()->addMinutes(15),
        ]);

        // Act
        $response = $this->postJson('/api/users/complete', [
            'name'       => 'Resource Test',
            'cpf'        => '52998224725',
            'birth_date' => '1990-01-01',
            'email'      => $email,
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'cpf', 'birth_date', 'created_at', 'updated_at'],
            ])
            ->assertJsonMissing(['google_token'])
            ->assertJsonMissing(['google_email'])
            ->assertJsonMissing(['deleted_at']);
    }
}
