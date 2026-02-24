<?php

namespace Tests\Unit\Modules\User\Rules;

use App\Modules\User\Rules\CpfRule;
use PHPUnit\Framework\TestCase;

class CpfRuleTest extends TestCase
{
    /** @phpstan-ignore property.uninitialized */
    private CpfRule $rule;

    /** @var array<string> */
    private array $errors = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule   = new CpfRule();
        $this->errors = [];
    }

    private function validate(mixed $value): bool
    {
        $this->errors = [];

        /** @phpstan-ignore argument.type */
        $this->rule->validate('cpf', $value, function (string $message) {
            $this->errors[] = $message;
        });

        return empty($this->errors);
    }

    public function testValidCpfWithOnlyDigits(): void
    {
        // Arrange
        $cpf = '52998224725';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertTrue($result);
    }

    public function testValidCpfWithFormatting(): void
    {
        // Arrange
        $cpf = '529.982.247-25';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertTrue($result);
    }

    public function testAnotherValidCpf(): void
    {
        // Arrange
        $cpf = '11144477735';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertTrue($result);
    }

    public function testInvalidCpfWithWrongCheckDigits(): void
    {
        // Arrange
        $cpf = '11122233344';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertFalse($result);
    }

    public function testAllRepeatedDigitsAreInvalid(): void
    {
        // Arrange
        $allRepeated = [
            '00000000000', '11111111111', '22222222222',
            '33333333333', '44444444444', '55555555555',
            '66666666666', '77777777777', '88888888888',
            '99999999999',
        ];

        foreach ($allRepeated as $cpf) {
            // Act
            $result = $this->validate($cpf);

            // Assert
            $this->assertFalse($result, "CPF {$cpf} deveria ser inválido");
        }
    }

    public function testCpfWithLessThan11DigitsIsInvalid(): void
    {
        // Arrange
        $cpf = '1234567890';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertFalse($result);
    }

    public function testCpfWithMoreThan11DigitsIsInvalid(): void
    {
        // Arrange
        $cpf = '123456789012';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertFalse($result);
    }

    public function testEmptyStringIsInvalid(): void
    {
        // Arrange
        $cpf = '';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertFalse($result);
    }

    public function testNonStringValueIsInvalid(): void
    {
        // Arrange
        $cpf = 12345678900;

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertFalse($result);
    }

    public function testCpfWithLettersIsInvalid(): void
    {
        // Arrange
        $cpf = 'abcdefghijk';

        // Act
        $result = $this->validate($cpf);

        // Assert
        $this->assertFalse($result);
    }
}
