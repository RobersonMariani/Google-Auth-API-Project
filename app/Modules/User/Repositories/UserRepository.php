<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repositório responsável pelas operações de acesso aos dados da model User.
 */
class UserRepository implements UserRepositoryInterface
{
    /**
     * Busca um usuário pelo e-mail autenticado no Google.
     *
     */
    public function findByGoogleEmail(string $email): ?User
    {
        return User::where('google_email', $email)->first();
    }

    /**
     * Cria um novo usuário no banco de dados.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Atualiza os dados de um usuário existente.
     *
     * @param array<string, mixed> $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user;
    }

    /**
     * Obtém uma lista paginada de usuários filtrados por nome ou CPF.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsersFilteredByNameOrCpf(?string $name, ?string $cpf, int $perPage = 20): LengthAwarePaginator
    {
        $cpf = $cpf ? preg_replace('/\D/', '', $cpf) : null;

        return User::when($name, fn ($q) => $q->where('name', 'like', '%' . $name . '%'))
            ->when($cpf, fn ($q) => $q->where('cpf', 'like', $cpf . '%'))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }
}
