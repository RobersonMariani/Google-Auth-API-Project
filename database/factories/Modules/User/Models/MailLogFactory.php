<?php

namespace Database\Factories\Modules\User\Models;

use App\Modules\User\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\User\Models\MailLog>
 */
class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_email' => $this->faker->unique()->safeEmail,
            'subject' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['sent', 'failed']),
            'sent_at' => $this->faker->dateTime(),
        ];
    }
}
