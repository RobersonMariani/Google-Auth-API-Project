<?php

namespace App\Modules\User\Services;

use App\Modules\User\DTOs\CompleteUserDataDTO;
use App\Modules\User\DTOs\UserFilterDTO;
use App\Modules\User\Enums\AuthMessagesEnum;
use App\Modules\User\Jobs\SendRegistrationEmailJob;
use App\Modules\User\Models\TemporaryUser;
use App\Modules\User\Models\User;
use App\Modules\User\Repositories\UserRepositoryInterface;
use Exception;
use Google_Client;
use Google_Service_Oauth2;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

/**
 * Serviço responsável pela lógica de cadastro e listagem de usuários.
 */
class UserService
{
    public function __construct(protected UserRepositoryInterface $repository)
    {
    }

    /**
     * Retorna uma lista paginada de usuários com base nos filtros fornecidos.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function listUsers(UserFilterDTO $filters): LengthAwarePaginator
    {
        return $this->repository->getUsersFilteredByNameOrCpf(
            $filters->name,
            $filters->cpf,
            $filters->perPage
        );
    }

    /**
     * Finaliza o cadastro de um usuário autenticado via Google.
     *
     *
     * @throws Exception Se o token for inválido ou não encontrar usuário temporário
     */
    public function completeUserData(CompleteUserDataDTO $dto): User
    {
        $temporaryUser = TemporaryUser::notExpired()
            ->where('email', $this->getEmailFromToken($dto->google_token))
            ->first();

        if (!$temporaryUser) {
            throw new Exception(AuthMessagesEnum::TOKEN_INVALID->value);
        }

        $user = $this->repository->create([
            'name'         => $dto->name,
            'cpf'          => $dto->cpf,
            'birth_date'   => $dto->birth_date,
            'google_token' => Crypt::decryptString($temporaryUser->google_token),
            'google_email' => $temporaryUser->email,
        ]);

        dispatch(new SendRegistrationEmailJob($temporaryUser->email));

        $temporaryUser->delete();

        return $user;
    }

    /**
     * Retorna o e-mail do usuário autenticado com base no token do Google.
     *
     */
    protected function getEmailFromToken(string $token): string
    {
        $client = new Google_Client();
        $client->setAccessToken($token);

        $oauth = new Google_Service_Oauth2($client);
        $info  = $oauth->userinfo->get();

        return $info->email;
    }
}
