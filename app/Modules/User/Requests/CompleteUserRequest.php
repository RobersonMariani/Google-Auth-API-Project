<?php

namespace App\Modules\User\Requests;

use App\Modules\User\DTOs\CompleteUserDataDTO;
use App\Modules\User\Rules\CpfRule;
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
            'name'       => 'required|string|max:255',
            'cpf'        => ['required', 'string', 'max:14', new CpfRule()],
            'birth_date' => 'required|date|before:today|after:1900-01-01',
            'email'      => 'required|email|unique:users,google_email',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
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
        /** @var array{name: string, cpf: string, birth_date: string, email: string} $data */
        $data = $this->validated();

        return new CompleteUserDataDTO(
            $data['name'],
            preg_replace('/\D/', '', $data['cpf']) ?? '',
            $data['birth_date'],
            $data['email']
        );
    }
}
