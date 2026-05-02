<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidPasswordHash;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordHashTest extends TestCase
{
    public function testBcryptShapedHashIsAccepted(): void
    {
        $hash = PasswordHash::fromString('$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghi');

        self::assertInstanceOf(PasswordHash::class, $hash);
    }

    public function testArgon2idShapedHashIsAccepted(): void
    {
        $hash = PasswordHash::fromString('$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$N2ZlYjQ1Njc4OWFiY2RlZg');

        self::assertInstanceOf(PasswordHash::class, $hash);
    }

    public function testValuePreservesExactInput(): void
    {
        $rawHash = '$2b$12$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghi';

        $hash = PasswordHash::fromString($rawHash);

        self::assertSame($rawHash, $hash->value());
    }

    public function testEqualsReturnsTrueForSameHash(): void
    {
        $rawHash = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghi';

        $first = PasswordHash::fromString($rawHash);
        $second = PasswordHash::fromString($rawHash);

        self::assertTrue($first->equals($second));
    }

    public function testEqualsReturnsFalseForDifferentHash(): void
    {
        $first = PasswordHash::fromString('$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghi');
        $second = PasswordHash::fromString('$2y$13$0123456789abcdefghijklmnopq.DcBa9876543210abcdefABCDEF');

        self::assertFalse($first->equals($second));
    }

    public function testEmptyStringIsRejected(): void
    {
        $this->expectException(InvalidPasswordHash::class);

        PasswordHash::fromString('');
    }

    public function testWhitespaceOnlyStringIsRejected(): void
    {
        $this->expectException(InvalidPasswordHash::class);

        PasswordHash::fromString(" \n\t ");
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function obviousPlainPasswordProvider(): iterable
    {
        yield 'password' => ['password'];
        yield 'secret123' => ['secret123'];
        yield 'qwerty' => ['qwerty'];
        yield 'plain text password' => ['plain-text-password'];
        yield 'not a hash' => ['not-a-hash'];
    }

    #[DataProvider('obviousPlainPasswordProvider')]
    public function testObviousPlainPasswordIsRejected(string $plainPassword): void
    {
        $this->expectException(InvalidPasswordHash::class);

        PasswordHash::fromString($plainPassword);
    }

    public function testExceptionMessageDoesNotContainRejectedPasswordInput(): void
    {
        $rejectedInput = 'plain-text-password';

        try {
            PasswordHash::fromString($rejectedInput);
            self::fail('Expected invalid password hash exception.');
        } catch (InvalidPasswordHash $exception) {
            self::assertStringNotContainsString($rejectedInput, $exception->getMessage());
        }
    }
}
