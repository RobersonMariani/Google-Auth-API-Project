<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\User\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'cpf' => $this->generateValidCpf(),
            'birth_date' => $this->faker->date('Y-m-d'),
            'google_token' => 'mocked-token',
            'google_email' => $this->faker->unique()->safeEmail,
        ];
    }
    
    /**
     * Generate a valid CPF number.
     *
     * @return string
     */
    private function generateValidCpf(): string
    {
        $cpf = [];

        for ($i = 0; $i < 9; $i++) {
            $cpf[] = rand(0, 9);
        }

        // Calcula o primeiro dígito verificador
        $sum = 0;
        for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) {
            $sum += $cpf[$i] * $weight;
        }
        $rest = $sum % 11;
        $cpf[] = ($rest < 2) ? 0 : 11 - $rest;

        // Segundo dígito verificador
        $sum = 0;
        for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) {
            $sum += $cpf[$i] * $weight;
        }
        $rest = $sum % 11;
        $cpf[] = ($rest < 2) ? 0 : 11 - $rest;

        return implode('', $cpf);
    }
}
