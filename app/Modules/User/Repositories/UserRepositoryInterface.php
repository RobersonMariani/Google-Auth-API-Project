<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface para o repositório de usuários.
 * Define os contratos das operações de persistência e consulta relacionadas à model User.
 */
interface UserRepositoryInterface
{
    /**
     * Retorna um usuário com base no e-mail autenticado via Google.
     *
     */
    public function findByGoogleEmail(string $email): ?User;

    /**
     * Cria um novo usuário.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): User;

    /**
     * Atualiza os dados de um usuário existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): User;

    /**
     * Retorna uma lista paginada de usuários, com filtros opcionais por nome e CPF.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsersFilteredByNameOrCpf(?string $name, ?string $cpf, int $perPage = 20): LengthAwarePaginator;
}
