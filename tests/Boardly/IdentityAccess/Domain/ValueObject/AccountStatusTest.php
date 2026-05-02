<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\ValueObject;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccountStatusTest extends TestCase
{
    public function testPendingApprovalConstructorReturnsExpectedValue(): void
    {
        self::assertSame('pending_approval', AccountStatus::pendingApproval()->value());
    }

    public function testActiveConstructorReturnsExpectedValue(): void
    {
        self::assertSame('active', AccountStatus::active()->value());
    }

    public function testRejectedConstructorReturnsExpectedValue(): void
    {
        self::assertSame('rejected', AccountStatus::rejected()->value());
    }

    public function testDisabledConstructorReturnsExpectedValue(): void
    {
        self::assertSame('disabled', AccountStatus::disabled()->value());
    }

    #[DataProvider('validStatuses')]
    public function testFromStringAcceptsValidStatus(string $value): void
    {
        self::assertSame($value, AccountStatus::fromString($value)->value());
    }

    public function testEmptyStringIsRejected(): void
    {
        $this->expectException(InvalidAccountStatus::class);

        AccountStatus::fromString('');
    }

    public function testUnknownStatusIsRejected(): void
    {
        $this->expectException(InvalidAccountStatus::class);

        AccountStatus::fromString('archived');
    }

    public function testEqualsReturnsTrueForSameStatus(): void
    {
        self::assertTrue(AccountStatus::active()->equals(AccountStatus::fromString('active')));
    }

    public function testEqualsReturnsFalseForDifferentStatus(): void
    {
        self::assertFalse(AccountStatus::active()->equals(AccountStatus::disabled()));
    }

    public function testPendingApprovalStateHelpersReturnCorrectBooleans(): void
    {
        $status = AccountStatus::pendingApproval();

        self::assertTrue($status->isPendingApproval());
        self::assertFalse($status->isActive());
        self::assertFalse($status->isRejected());
        self::assertFalse($status->isDisabled());
    }

    public function testActiveStateHelpersReturnCorrectBooleans(): void
    {
        $status = AccountStatus::active();

        self::assertFalse($status->isPendingApproval());
        self::assertTrue($status->isActive());
        self::assertFalse($status->isRejected());
        self::assertFalse($status->isDisabled());
    }

    public function testRejectedStateHelpersReturnCorrectBooleans(): void
    {
        $status = AccountStatus::rejected();

        self::assertFalse($status->isPendingApproval());
        self::assertFalse($status->isActive());
        self::assertTrue($status->isRejected());
        self::assertFalse($status->isDisabled());
    }

    public function testDisabledStateHelpersReturnCorrectBooleans(): void
    {
        $status = AccountStatus::disabled();

        self::assertFalse($status->isPendingApproval());
        self::assertFalse($status->isActive());
        self::assertFalse($status->isRejected());
        self::assertTrue($status->isDisabled());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validStatuses(): iterable
    {
        yield 'pending approval' => ['pending_approval'];
        yield 'active' => ['active'];
        yield 'rejected' => ['rejected'];
        yield 'disabled' => ['disabled'];
    }
}
