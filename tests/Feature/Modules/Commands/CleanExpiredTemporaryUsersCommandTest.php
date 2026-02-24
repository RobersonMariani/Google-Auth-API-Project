<?php

namespace Tests\Feature\Modules\Commands;

use App\Modules\User\Models\TemporaryUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CleanExpiredTemporaryUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createTemporaryUser(string $email, int $minutesOffset): TemporaryUser
    {
        return TemporaryUser::create([
            'email'        => $email,
            'google_id'    => Crypt::encryptString('gid-' . $email),
            'google_token' => Crypt::encryptString('token-' . $email),
            'expires_at'   => now()->addMinutes($minutesOffset),
        ]);
    }

    public function testDeletesExpiredTemporaryUsers(): void
    {
        // Arrange
        $this->createTemporaryUser('expired1@test.com', -30);
        $this->createTemporaryUser('expired2@test.com', -1);
        $this->createTemporaryUser('valid@test.com', 10);

        // Act
        $this->artisan('temporary-users:cleanup')
            ->expectsOutputToContain('2')
            ->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('temporary_users', ['email' => 'expired1@test.com']);
        $this->assertDatabaseMissing('temporary_users', ['email' => 'expired2@test.com']);
        $this->assertDatabaseHas('temporary_users', ['email' => 'valid@test.com']);
    }

    public function testWithNoExpiredRecordsDeletesNothing(): void
    {
        // Arrange
        $this->createTemporaryUser('valid1@test.com', 10);
        $this->createTemporaryUser('valid2@test.com', 15);

        // Act
        $this->artisan('temporary-users:cleanup')
            ->expectsOutputToContain('0')
            ->assertSuccessful();

        // Assert
        $this->assertDatabaseCount('temporary_users', 2);
    }

    public function testWithEmptyTableRunsWithoutError(): void
    {
        // Arrange — tabela vazia

        // Act
        $this->artisan('temporary-users:cleanup')
            ->expectsOutputToContain('0')
            ->assertSuccessful();

        // Assert
        $this->assertDatabaseCount('temporary_users', 0);
    }
}
