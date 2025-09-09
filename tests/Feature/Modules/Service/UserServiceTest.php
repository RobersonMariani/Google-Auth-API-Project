<?php

namespace Tests\Feature\Modules\Service;

use App\Modules\User\DTOs\CompleteUserDataDTO;
use App\Modules\User\DTOs\UserFilterDTO;
use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepositoryInterface;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o método completeUserData() do UserService,
     * verificando se ele transforma corretamente um usuário temporário em um usuário definitivo.
     */
    public function testCompleteUserDataCreatesUserFromTemporary(): void
    {
        $email = 'service@test.com';
        $token = 'mocked-token';

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('some-id'),
            'google_token' => Crypt::encryptString($token),
        ]);

        $repository = app(UserRepositoryInterface::class);

        /** @var UserService&\Mockery\MockInterface $service */
        $service = Mockery::mock(UserService::class, [$repository])->makePartial();
        $service->shouldAllowMockingProtectedMethods();
        $service->allows(['getEmailFromToken' => $email]);

        $dto = new CompleteUserDataDTO(
            name: 'Novo Teste',
            cpf: '99988877766',
            birth_date: '1985-05-10',
            google_token: $token
        );

        $user = $service->completeUserData($dto);

        $this->assertDatabaseHas('users', [
            'google_email' => $email,
            'cpf'          => '99988877766',
        ]);

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * Testa o método listUsers() do UserService com filtros aplicados.
     */
    public function testListUsersReturnsFilteredResults(): void
    {
        User::factory()->create([
            'name' => 'Roberson',
            'cpf'  => '12345678900',
        ]);

        $repository = app(UserRepositoryInterface::class);
        $service    = new UserService($repository);

        $dto = new UserFilterDTO('Roberson', '123.456.789-00', 20);

        $results = $service->listUsers($dto);

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);

        $items = $results->items();
        $this->assertNotEmpty($items);

        $first = $items[0];
        $this->assertInstanceOf(User::class, $first);
        $this->assertSame('Roberson', $first->name);
    }
}
