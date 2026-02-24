<?php

namespace Tests\Feature\Modules\Repositories;

use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    public function testCreateUserPersistsInDatabase(): void
    {
        // Arrange
        $data = [
            'name'         => 'Repo Teste',
            'cpf'          => '11122233344',
            'birth_date'   => '1990-01-01',
            'google_email' => 'repo@teste.com',
            'google_token' => 'encrypted-token',
        ];

        // Act
        $user = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['google_email' => 'repo@teste.com', 'cpf' => '11122233344']);
    }

    public function testFindByGoogleEmailReturnsUser(): void
    {
        // Arrange
        $user = User::factory()->create(['google_email' => 'find@teste.com']);

        // Act
        $found = $this->repository->findByGoogleEmail('find@teste.com');

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($user->id, $found->id);
    }

    public function testFindByGoogleEmailReturnsNullWhenNotFound(): void
    {
        // Arrange — banco vazio

        // Act
        $found = $this->repository->findByGoogleEmail('nonexistent@teste.com');

        // Assert
        $this->assertNull($found);
    }

    public function testUpdateUserChangesAttributes(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Nome Original', 'cpf' => '11111111111']);

        // Act
        $updated = $this->repository->update($user, ['name' => 'Novo Nome', 'cpf' => '88899900077']);

        // Assert
        $this->assertEquals('Novo Nome', $updated->name);
        $this->assertEquals('88899900077', $updated->cpf);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Novo Nome']);
    }

    public function testFilterByNameOnly(): void
    {
        // Arrange
        User::factory()->create(['name' => 'Maria Silva']);
        User::factory()->create(['name' => 'João Santos']);

        // Act
        $results = $this->repository->getUsersFilteredByNameOrCpf('Maria', null, 20);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertCount(1, $results->items());
        $this->assertEquals('Maria Silva', $results->items()[0]->name);
    }

    public function testFilterByCpfOnly(): void
    {
        // Arrange
        User::factory()->create(['cpf' => '12345678900']);
        User::factory()->create(['cpf' => '98765432100']);

        // Act
        $results = $this->repository->getUsersFilteredByNameOrCpf(null, '12345678900', 20);

        // Assert
        $this->assertCount(1, $results->items());
        $this->assertEquals('12345678900', $results->items()[0]->cpf);
    }

    public function testFilterByNameAndCpf(): void
    {
        // Arrange
        User::factory()->create(['name' => 'FiltroNome', 'cpf' => '12345678900']);
        User::factory()->create(['name' => 'FiltroNome', 'cpf' => '99988877766']);

        // Act
        $results = $this->repository->getUsersFilteredByNameOrCpf('FiltroNome', '12345678900', 20);

        // Assert
        $this->assertCount(1, $results->items());
        $this->assertEquals('12345678900', $results->items()[0]->cpf);
    }

    public function testFilterWithNoResultsReturnsEmpty(): void
    {
        // Arrange
        User::factory()->create(['name' => 'João']);

        // Act
        $results = $this->repository->getUsersFilteredByNameOrCpf('NomeInexistente', null, 20);

        // Assert
        $this->assertCount(0, $results->items());
        $this->assertEquals(0, $results->total());
    }

    public function testFilterExcludesSoftDeletedUsers(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Deletado']);
        $user->delete();
        User::factory()->create(['name' => 'Ativo']);

        // Act
        $results = $this->repository->getUsersFilteredByNameOrCpf(null, null, 20);

        // Assert
        $this->assertCount(1, $results->items());
        $this->assertEquals('Ativo', $results->items()[0]->name);
    }

    public function testFilterReturnsOrderedByIdDesc(): void
    {
        // Arrange
        $first  = User::factory()->create(['name' => 'Primeiro']);
        $second = User::factory()->create(['name' => 'Segundo']);

        // Act
        $results = $this->repository->getUsersFilteredByNameOrCpf(null, null, 20);
        $items   = $results->items();

        // Assert
        $this->assertCount(2, $items);
        $this->assertTrue($items[0]->id > $items[1]->id);
    }
}
