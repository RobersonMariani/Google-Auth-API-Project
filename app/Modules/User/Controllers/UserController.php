<?php

namespace App\Modules\User\Controllers;

use App\Modules\User\DTOs\UserFilterDTO;
use App\Modules\User\Enums\UserMessagesEnum;
use App\Modules\User\Requests\CompleteUserRequest;
use App\Modules\User\Resources\UserResource;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador responsável pelas ações relacionadas aos usuários.
 */
class UserController extends Controller
{
    public function __construct(private UserService $service)
    {
    }

    /**
     * Lista os usuários cadastrados, aplicando filtros opcionais de nome e CPF.
     *
     */
    public function index(Request $request): JsonResponse
    {
        $filters = UserFilterDTO::fromRequest($request);
        $users   = $this->service->listUsers($filters);

        return response()->json([
            'message' => UserMessagesEnum::LISTING,
            'data'    => UserResource::collection($users),
        ], Response::HTTP_OK);
    }

    /**
     * Finaliza o cadastro de um usuário autenticado via Google.
     *
     */
    public function complete(CompleteUserRequest $request): JsonResponse
    {
        $user = $this->service->completeUserData(
            $request->toDTO()
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => UserMessagesEnum::CREATED,
            'data'    => new UserResource($user),
            'token'   => $token,
        ], Response::HTTP_CREATED);
    }
}
