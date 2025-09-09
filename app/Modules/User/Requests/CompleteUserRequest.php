<?php

namespace App\Modules\User\Requests;

use App\Modules\User\DTOs\CompleteUserDataDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Request responsável por validar os dados enviados para finalização do cadastro do usuário.
 */
class CompleteUserRequest extends FormRequest
{
    /**
     * Define as regras de validação para a requisição.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'cpf'          => 'required|string|max:14',
            'birth_date'   => 'required|date',
            'google_token' => 'required|string',
        ];
    }

    /**
     * Define se o usuário está autorizado a fazer esta requisição.
     *
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Cria o DTO com os dados validados da requisição.
     *
     */
    public function toDTO(): CompleteUserDataDTO
    {
        /** @var array{name: string, cpf: string, birth_date: string, google_token: string} $data */
        $data = $this->validated();

        return new CompleteUserDataDTO(
            $data['name'],
            preg_replace('/\D/', '', $data['cpf']) ?? '',
            $data['birth_date'],
            $data['google_token']
        );
    }
}
