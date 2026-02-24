<?php

namespace Tests\Feature\Modules\Service;

use App\Modules\User\DTOs\CompleteUserDataDTO;
use App\Modules\User\DTOs\UserFilterDTO;
use App\Modules\User\Enums\AuthMessagesEnum;
use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepositoryInterface;
use App\Modules\User\Services\UserService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createServiceWithMockedToken(string $email): UserService
    {
        $repository = app(UserRepositoryInterface::class);

        /** @var UserService&\Mockery\MockInterface $service */
        $service = Mockery::mock(UserService::class, [$repository])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->allows(['getEmailFromToken' => $email]);

        return $service;
    }

    public function testCompleteUserDataCreatesUserFromTemporary(): void
    {
        // Arrange
        $email   = 'service@test.com';
        $token   = 'mocked-token';
        $service = $this->createServiceWithMockedToken($email);

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('some-id'),
            'google_token' => Crypt::encryptString($token),
            'expires_at'   => now()->addMinutes(15),
        ]);

        $dto = new CompleteUserDataDTO(
            name: 'Novo Teste',
            cpf: '99988877766',
            birth_date: '1985-05-10',
            google_token: $token
        );

        // Act
        $user = $service->completeUserData($dto);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['google_email' => $email, 'cpf' => '99988877766']);
    }

    public function testCompleteUserDataDeletesTemporaryUserAfter(): void
    {
        // Arrange
        $email   = 'delete@test.com';
        $token   = 'mocked-token';
        $service = $this->createServiceWithMockedToken($email);

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('gid'),
            'google_token' => Crypt::encryptString($token),
            'expires_at'   => now()->addMinutes(15),
        ]);

        $dto = new CompleteUserDataDTO(
            name: 'Delete Test',
            cpf: '88877766655',
            birth_date: '1992-03-20',
            google_token: $token
        );

        // Act
        $service->completeUserData($dto);

        // Assert
        $this->assertDatabaseMissing('temporary_users', ['email' => $email]);
    }

    public function testCompleteUserDataWithExpiredTemporaryThrowsException(): void
    {
        // Arrange
        $email   = 'expired@test.com';
        $token   = 'mocked-token';
        $service = $this->createServiceWithMockedToken($email);

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('gid'),
            'google_token' => Crypt::encryptString($token),
            'expires_at'   => now()->subMinutes(1),
        ]);

        $dto = new CompleteUserDataDTO(
            name: 'Expired Test',
            cpf: '77766655544',
            birth_date: '1990-01-01',
            google_token: $token
        );

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(AuthMessagesEnum::TOKEN_INVALID->value);

        // Act
        $service->completeUserData($dto);
    }

    public function testCompleteUserDataWithNonExistingTemporaryThrowsException(): void
    {
        // Arrange
        $service = $this->createServiceWithMockedToken('ghost@test.com');

        $dto = new CompleteUserDataDTO(
            name: 'Ghost User',
            cpf: '66655544433',
            birth_date: '1988-07-04',
            google_token: 'non-existing-token'
        );

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(AuthMessagesEnum::TOKEN_INVALID->value);

        // Act
        $service->completeUserData($dto);
    }

    public function testListUsersReturnsFilteredResults(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Roberson', 'cpf' => '12345678900']);
        User::factory()->create(['name' => 'João', 'cpf' => '98765432100']);

        $repository = app(UserRepositoryInterface::class);
        $service    = new UserService($repository);
        $dto        = new UserFilterDTO('Roberson', null, 20);

        // Act
        $results = $service->listUsers($dto);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertCount(1, $results->items());
        $this->assertSame('Roberson', $results->items()[0]->name);
    }

    public function testListUsersWithNoFiltersReturnsAll(): void
    {
        // Arrange
        User::factory()->count(3)->create();

        $repository = app(UserRepositoryInterface::class);
        $service    = new UserService($repository);
        $dto        = new UserFilterDTO(null, null, 20);

        // Act
        $results = $service->listUsers($dto);

        // Assert
        $this->assertCount(3, $results->items());
    }
}
