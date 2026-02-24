<?php

namespace Tests\Unit\Modules\User\DTOs;

use App\Modules\User\DTOs\UserFilterDTO;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class UserFilterDTOTest extends TestCase
{
    public function testConstructorRemovesCpfFormatting(): void
    {
        // Arrange
        $cpf = '123.456.789-00';

        // Act
        $dto = new UserFilterDTO('Nome', $cpf, 20);

        // Assert
        $this->assertSame('12345678900', $dto->cpf);
    }

    public function testConstructorKeepsNullCpf(): void
    {
        // Arrange & Act
        $dto = new UserFilterDTO('Nome', null, 20);

        // Assert
        $this->assertNull($dto->cpf);
    }

    public function testFromRequestWithDefaults(): void
    {
        // Arrange
        $request = Request::create('/api/users', 'GET');

        // Act
        $dto = UserFilterDTO::fromRequest($request);

        // Assert
        $this->assertNull($dto->name);
        $this->assertNull($dto->cpf);
        $this->assertSame(20, $dto->perPage);
    }

    public function testFromRequestWithAllParams(): void
    {
        // Arrange
        $request = Request::create('/api/users', 'GET', [
            'name'     => 'João',
            'cpf'      => '123.456.789-00',
            'per_page' => '50',
        ]);

        // Act
        $dto = UserFilterDTO::fromRequest($request);

        // Assert
        $this->assertSame('João', $dto->name);
        $this->assertSame('12345678900', $dto->cpf);
        $this->assertSame(50, $dto->perPage);
    }

    public function testPerPageIsCappedAt100(): void
    {
        // Arrange
        $request = Request::create('/api/users', 'GET', ['per_page' => '999']);

        // Act
        $dto = UserFilterDTO::fromRequest($request);

        // Assert
        $this->assertSame(100, $dto->perPage);
    }

    public function testPerPageMinimumIs1(): void
    {
        // Arrange
        $request = Request::create('/api/users', 'GET', ['per_page' => '0']);

        // Act
        $dto = UserFilterDTO::fromRequest($request);

        // Assert
        $this->assertSame(20, $dto->perPage);
    }

    public function testPerPageWithNegativeValueIsClampedTo1(): void
    {
        // Arrange
        $request = Request::create('/api/users', 'GET', ['per_page' => '-5']);

        // Act
        $dto = UserFilterDTO::fromRequest($request);

        // Assert
        $this->assertSame(1, $dto->perPage);
    }

    public function testPerPageWithNonNumericValueFallsToDefault(): void
    {
        // Arrange
        $request = Request::create('/api/users', 'GET', ['per_page' => 'abc']);

        // Act
        $dto = UserFilterDTO::fromRequest($request);

        // Assert
        $this->assertSame(20, $dto->perPage);
    }
}
