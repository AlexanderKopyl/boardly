<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testValidEmailIsAccepted(): void
    {
        $email = Email::fromString('user@example.com');

        self::assertInstanceOf(Email::class, $email);
    }

    public function testValueReturnsNormalizedLowercaseEmail(): void
    {
        $email = Email::fromString('USER@EXAMPLE.COM');

        self::assertSame('user@example.com', $email->value());
    }

    public function testLeadingAndTrailingSpacesAreTrimmed(): void
    {
        $email = Email::fromString(' user@example.com ');

        self::assertSame('user@example.com', $email->value());
    }

    public function testUppercaseEmailIsNormalizedToLowercase(): void
    {
        $email = Email::fromString('User.Name+Tag@Example.COM');

        self::assertSame('user.name+tag@example.com', $email->value());
    }

    public function testEqualsReturnsTrueForSameNormalizedEmail(): void
    {
        $first = Email::fromString(' User@Example.COM ');
        $second = Email::fromString('user@example.com');

        self::assertTrue($first->equals($second));
    }

    public function testEqualsReturnsFalseForDifferentEmail(): void
    {
        $first = Email::fromString('first@example.com');
        $second = Email::fromString('second@example.com');

        self::assertFalse($first->equals($second));
    }

    public function testEmptyStringIsRejected(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::fromString('');
    }

    public function testWhitespaceOnlyStringIsRejected(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::fromString('   ');
    }

    public function testInvalidEmailStringIsRejected(): void
    {
        $this->expectException(InvalidEmail::class);

        Email::fromString('not-an-email');
    }
}
