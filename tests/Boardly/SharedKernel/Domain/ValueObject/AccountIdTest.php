<?php

declare(strict_types=1);

namespace App\Tests\Boardly\SharedKernel\Domain\ValueObject;

use App\Boardly\SharedKernel\Domain\Exception\InvalidAccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountIdTest extends TestCase
{
    public function testValidUuidIsAccepted(): void
    {
        $accountId = AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d');

        self::assertInstanceOf(AccountId::class, $accountId);
    }

    public function testValueReturnsUuidString(): void
    {
        $uuid = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

        $accountId = AccountId::fromString($uuid);

        self::assertSame($uuid, $accountId->value());
    }

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

        self::assertTrue(AccountId::fromString($uuid)->equals(AccountId::fromString($uuid)));
    }

    public function testEqualsReturnsFalseForDifferentUuid(): void
    {
        $first = AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d');
        $second = AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e');

        self::assertFalse($first->equals($second));
    }

    public function testEmptyStringIsRejected(): void
    {
        $this->expectException(InvalidAccountId::class);

        AccountId::fromString('');
    }

    public function testWhitespaceOnlyStringIsRejected(): void
    {
        $this->expectException(InvalidAccountId::class);

        AccountId::fromString('   ');
    }

    public function testInvalidUuidStringIsRejected(): void
    {
        $this->expectException(InvalidAccountId::class);

        AccountId::fromString('not-a-uuid');
    }
}
