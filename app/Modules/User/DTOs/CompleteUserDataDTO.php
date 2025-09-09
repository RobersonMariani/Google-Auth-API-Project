<?php

namespace App\Modules\User\DTOs;

/**
 * DTO responsável por representar os dados completos de um usuário para finalização de cadastro.
 *
 * @property-read string $name
 * @property-read string $cpf
 * @property-read string $birth_date
 * @property-read string $google_token
 */
readonly class CompleteUserDataDTO
{
    public function __construct(
        public string $name,
        public string $cpf,
        public string $birth_date,
        public string $google_token
    ) {
    }
}
