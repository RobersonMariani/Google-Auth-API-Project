<?php

namespace App\Modules\User\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regra de validação para CPF brasileiro.
 * Verifica formato e dígitos verificadores.
 */
class CpfRule implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O campo :attribute deve ser uma string.');
            return;
        }

        $cpf = preg_replace('/\D/', '', $value);

        if ($cpf === null || strlen($cpf) !== 11) {
            $fail('O campo :attribute deve conter 11 dígitos.');
            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O campo :attribute informado é inválido.');
            return;
        }

        if (!$this->validateDigits($cpf)) {
            $fail('O campo :attribute informado é inválido.');
        }
    }

    /**
     * Valida os dígitos verificadores do CPF.
     */
    private function validateDigits(string $cpf): bool
    {
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
