<?php

namespace Tests\Feature\Modules\Repositories;

use App\Modules\User\DTOs\UserFilterDTO;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @var UserRepository */
    protected $repository;

    /**
     * Inicializa o repositório antes de cada teste.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    /**
     * Testa o método create() do repositório.
     * Verifica se um usuário é criado corretamente no banco de dados.
     */
    public function testCreateUser(): void
    {
        $user = $this->repository->create([
            'name'         => 'Repo Teste',
            'cpf'          => '11122233344',
            'birth_date'   => '1990-01-01',
            'google_email' => 'repo@teste.com',
            'google_token' => 'encrypted-token',
        ]);

        $this->assertDatabaseHas('users', [
            'google_email' => 'repo@teste.com',
            'cpf'          => '11122233344',
        ]);

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * Testa o método findByGoogleEmail() do repositório.
     * Verifica se é possível encontrar um usuário pelo e-mail do Google.
     */
    public function testFindByGoogleEmail(): void
    {
        $user = User::factory()->create([
            'google_email' => 'find@teste.com',
        ]);

        $found = $this->repository->findByGoogleEmail('find@teste.com');

        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    /**
     * Testa o método update() do repositório.
     * Verifica se os dados de um usuário são atualizados corretamente.
     */
    public function testUpdateUser(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);

        $updated = $this->repository->update($user, [
            'name' => 'Novo Nome',
            'cpf'  => '88899900077',
        ]);

        $this->assertEquals('Novo Nome', $updated->name);
        $this->assertEquals('88899900077', $updated->cpf);
    }

    /**
     * Testa o método getUsersFilteredByNameOrCpf() com filtros por nome e CPF.
     * Verifica se os filtros aplicados retornam os dados esperados.
     */
    public function testGetUsersFilteredByNameOrCpfWithNameAndCpf(): void
    {
        User::factory()->create(['name' => 'FiltroNome', 'cpf' => '12345678900']);

        $dto = new UserFilterDTO(
            name: 'FiltroNome',
            cpf: '123.456.789-00',
            perPage: 20
        );

        $results = $this->repository->getUsersFilteredByNameOrCpf($dto->name, $dto->cpf, $dto->perPage);

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertGreaterThan(0, $results->total());

        $items = $results->items();
        $this->assertNotEmpty($items);

        $firstUser = $items[0];
        $this->assertInstanceOf(User::class, $firstUser);
        $this->assertEquals('FiltroNome', $firstUser->name);
    }
}
