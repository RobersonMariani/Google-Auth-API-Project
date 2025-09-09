<?php

namespace Tests\Feature\Modules\Controller;

use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepositoryInterface;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o endpoint de listagem de usuários (/api/users)
     * Verifica se os dados inseridos no banco são retornados corretamente na resposta.
     */
    public function testUserIndexReturnsData(): void
    {
        User::factory()->create(['name' => 'Roberson', 'cpf' => '12345678900']);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Roberson']);
    }

    /**
     * Testa o endpoint de finalização de cadastro do usuário (/api/users/complete)
     * Verifica se o sistema cria corretamente um usuário com base nos dados temporários do Google.
     */
    public function testUserCompleteCreatesUserFromTemporary(): void
    {
        $email    = 'test@example.com';
        $token    = 'mocked-token';
        $googleId = 'google-id-456';

        TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString($googleId),
            'google_token' => Crypt::encryptString($token),
        ]);

        $repository = app(UserRepositoryInterface::class);

        /** @var \App\Modules\User\Services\UserService&\Mockery\MockInterface $mock */
        $mock = Mockery::mock(UserService::class, [$repository])->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows([
            'getEmailFromToken' => $email,
        ]);

        $this->app->instance(UserService::class, $mock);

        $response = $this->postJson('/api/users/complete', [
            'name'         => 'Novo Usuário',
            'cpf'          => '98765432100',
            'birth_date'   => '1999-12-31',
            'google_token' => $token,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'Usuário criado com sucesso.']);

        $this->assertDatabaseHas('users', [
            'google_email' => $email,
            'cpf'          => '98765432100',
        ]);
    }
}
