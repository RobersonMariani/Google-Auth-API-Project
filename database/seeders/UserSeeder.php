<?php

namespace Database\Seeders;

use App\Modules\User\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $total = 150000;
        $batchSize = 5000;

        for ($i = 0; $i < $total; $i += $batchSize) {
            User::factory()->count($batchSize)->create();
            $this->command->info("Inseridos {$i} registros...");
        }

        $this->command->info('Seed de usuários completo!');
    }
}
