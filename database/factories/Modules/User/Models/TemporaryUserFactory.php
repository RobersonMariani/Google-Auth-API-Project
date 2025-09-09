<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\TemporaryUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\User\Models\TemporaryUser>
 */
class TemporaryUserFactory extends Factory
{
    protected $model = TemporaryUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'google_id' => $this->faker->uuid,
            'google_token' => $this->faker->uuid,
        ];
    }
}
