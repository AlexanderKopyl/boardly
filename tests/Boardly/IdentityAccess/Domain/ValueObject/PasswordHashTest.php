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
        $hash = PasswordHash::fromString('$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij');

        self::assertInstanceOf(PasswordHash::class, $hash);
    }

    public function testArgon2idShapedHashIsAccepted(): void
    {
        $hash = PasswordHash::fromString('$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$N2ZlYjQ1Njc4OWFiY2RlZg');

        self::assertInstanceOf(PasswordHash::class, $hash);
    }

    public function testValuePreservesExactInput(): void
    {
        $rawHash = '$2b$12$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

        $hash = PasswordHash::fromString($rawHash);

        self::assertSame($rawHash, $hash->value());
    }

    public function testEqualsReturnsTrueForSameHash(): void
    {
        $rawHash = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

        $first = PasswordHash::fromString($rawHash);
        $second = PasswordHash::fromString($rawHash);

        self::assertTrue($first->equals($second));
    }

    public function testEqualsReturnsFalseForDifferentHash(): void
    {
        $first = PasswordHash::fromString('$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij');
        $second = PasswordHash::fromString('$2y$13$0123456789abcdefghijkl.DcBa9876543210abcdefABCDEFghij');

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

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedSupportedHashProvider(): iterable
    {
        yield 'bare 2y bcrypt prefix' => ['$2y$'];
        yield 'bare 2a bcrypt prefix' => ['$2a$'];
        yield 'bare 2b bcrypt prefix' => ['$2b$'];
        yield 'bare argon2id prefix' => ['$argon2id$'];
        yield 'malformed bcrypt-like value' => ['$2y$13$short'];
        yield 'malformed argon2id-like value' => ['$argon2id$v=19$m=65536,t=4,p=1$short'];
    }

    #[DataProvider('malformedSupportedHashProvider')]
    public function testMalformedSupportedHashIsRejected(string $hash): void
    {
        $this->expectException(InvalidPasswordHash::class);

        PasswordHash::fromString($hash);
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
