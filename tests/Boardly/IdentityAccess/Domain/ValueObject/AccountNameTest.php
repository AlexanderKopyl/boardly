<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use PHPUnit\Framework\TestCase;

final class AccountNameTest extends TestCase
{
    public function testValidNameIsAccepted(): void
    {
        $name = AccountName::fromString('Jane Doe');

        self::assertInstanceOf(AccountName::class, $name);
    }

    public function testValueReturnsTrimmedName(): void
    {
        $name = AccountName::fromString(' Jane Doe ');

        self::assertSame('Jane Doe', $name->value());
    }

    public function testInternalSpacesArePreserved(): void
    {
        $name = AccountName::fromString('Jane   Mary   Doe');

        self::assertSame('Jane   Mary   Doe', $name->value());
    }

    public function testEqualsReturnsTrueForSameNormalizedName(): void
    {
        $first = AccountName::fromString(' Jane Doe ');
        $second = AccountName::fromString('Jane Doe');

        self::assertTrue($first->equals($second));
    }

    public function testEqualsReturnsFalseForDifferentName(): void
    {
        $first = AccountName::fromString('Jane Doe');
        $second = AccountName::fromString('John Doe');

        self::assertFalse($first->equals($second));
    }

    public function testEmptyStringIsRejected(): void
    {
        $this->expectException(InvalidAccountName::class);

        AccountName::fromString('');
    }

    public function testWhitespaceOnlyStringIsRejected(): void
    {
        $this->expectException(InvalidAccountName::class);

        AccountName::fromString('   ');
    }

    public function testOneHundredCharacterNameIsAccepted(): void
    {
        $name = AccountName::fromString(str_repeat('a', 100));

        self::assertSame(str_repeat('a', 100), $name->value());
    }

    public function testOneHundredOneCharacterNameIsRejected(): void
    {
        $this->expectException(InvalidAccountName::class);

        AccountName::fromString(str_repeat('a', 101));
    }
}
