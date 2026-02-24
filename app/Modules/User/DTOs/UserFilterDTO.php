<?php

namespace App\Modules\User\DTOs;

use Illuminate\Http\Request;

/**
 * DTO responsável por encapsular os filtros aplicados na listagem de usuários.
 */
class UserFilterDTO
{
    public function __construct(
        public ?string $name,
        public ?string $cpf,
        public int $perPage = 20
    ) {
        // Remove qualquer formatação do CPF
        $this->cpf = $cpf ? preg_replace('/\D/', '', $cpf) : null;
    }

    /**
     * Cria uma instância do DTO a partir dos dados da requisição HTTP.
     *
     */
    public static function fromRequest(Request $request): self
    {
        /** @var string|null $name */
        $name = $request->input('name');

        /** @var string|null $cpf */
        $cpf = $request->input('cpf');

        /** @var int $perPage */
        $perPage = filter_var($request->input('per_page', 20), FILTER_VALIDATE_INT) ?: 20;
        $perPage = min(100, max(1, $perPage));

        return new self($name, $cpf, $perPage);
    }
}
