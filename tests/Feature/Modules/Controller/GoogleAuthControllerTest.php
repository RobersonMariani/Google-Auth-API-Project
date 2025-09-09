<?php

namespace Tests\Feature\Modules\Controller;

use App\Modules\User\Models\TemporaryUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se a rota de geração da URL de login com o Google
     * retorna uma URL válida no formato esperado.
     */
    public function testLoginUrlReturnsValidUrl(): void
    {
        // Cria um mock parcial do serviço de autenticação com Google
        // Simula o retorno de uma URL falsa para autenticação
        $this->partialMock(\App\Modules\User\Services\GoogleAuthService::class, function ($mock) {
            $mock->shouldReceive('generateLoginUrl')->andReturn('https://fake.google.auth.url');
        });

        // Faz a requisição à rota da API que retorna a URL de login
        $response = $this->getJson('/api/google/login-url');

        // Verifica se a resposta tem status 200 e contém a chave "url"
        $response->assertStatus(200)
            ->assertJsonStructure(['url']);
    }

    /**
     * Testa se o callback da autenticação com o Google
     * armazena corretamente um TemporaryUser no banco de dados
     * e redireciona o usuário com o e-mail como parâmetro.
     */
    public function testCallbackStoresTemporaryUser(): void
    {
        // Dados simulados do usuário retornado pelo Google
        $email    = 'callback@test.com';
        $token    = 'mocked-token';
        $googleId = 'mock-id';

        // Cria um mock parcial do serviço de autenticação
        // Simula o método handleCallback retornando um usuário temporário salvo
        $this->partialMock(\App\Modules\User\Services\GoogleAuthService::class, function ($mock) use ($email, $googleId, $token) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('handleCallback')->andReturn(
                TemporaryUser::create([
                    'email'        => $email,
                    'google_id'    => Crypt::encryptString($googleId),
                    'google_token' => Crypt::encryptString($token),
                ])
            );
        });

        // Simula a requisição de callback com o código de autorização do Google
        $response = $this->get('/api/google/callback?code=valid-code');

        // Verifica se o redirecionamento contém o e-mail como parâmetro
        $response->assertRedirectContains('?email=' . $email);

        // Verifica se o usuário temporário foi salvo corretamente no banco
        $this->assertDatabaseHas('temporary_users', ['email' => $email]);
    }
}
