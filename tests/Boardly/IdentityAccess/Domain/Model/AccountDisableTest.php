<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Event\AccountDisabled;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyDisabled;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountStatusTransition;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountDisableTest extends TestCase
{
    public function testActiveSystemAdminCanBeDisabled(): void
    {
        $account = Account::createSystemAdmin(
            $this->accountId(),
            Email::fromString('admin@example.com'),
            $this->passwordHash(),
            AccountName::fromString('System Admin'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        $account->disable(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        self::assertTrue($account->status()->isDisabled());
    }

    public function testApprovedRegularAccountCanBeDisabled(): void
    {
        $account = $this->approvedRegularAccount();

        $account->disable(new \DateTimeImmutable('2026-05-02T12:10:00+00:00'));

        self::assertTrue($account->status()->isDisabled());
    }

    public function testDisableSetsDisabledAtAndUpdatesUpdatedAt(): void
    {
        $account = $this->approvedRegularAccount();
        $disabledAt = new \DateTimeImmutable('2026-05-02T12:10:00+00:00');

        $account->disable($disabledAt);

        self::assertSame($disabledAt, $account->disabledAt());
        self::assertSame($disabledAt, $account->updatedAt());
    }

    public function testDisableKeepsApprovedAtAndRejectedAtUnchanged(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $account = $this->approvedRegularAccount($approvedAt);

        $account->disable(new \DateTimeImmutable('2026-05-02T12:10:00+00:00'));

        self::assertSame($approvedAt, $account->approvedAt());
        self::assertNull($account->rejectedAt());
    }

    public function testDisableReturnsAccountDisabledEvent(): void
    {
        $id = $this->accountId();
        $disabledAt = new \DateTimeImmutable('2026-05-02T12:10:00+00:00');
        $account = Account::register(
            $id,
            Email::fromString('registered@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();
        $account->approve(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        $event = $account->disable($disabledAt)->event();

        self::assertInstanceOf(AccountDisabled::class, $event);
        self::assertSame($id, $event->accountId());
        self::assertSame($disabledAt, $event->disabledAt());
    }

    public function testDisablingPendingAccountIsRejected(): void
    {
        $account = $this->pendingAccount();

        $this->expectException(InvalidAccountStatusTransition::class);

        $account->disable(new \DateTimeImmutable('2026-05-02T12:10:00+00:00'));
    }

    public function testDisablingRejectedAccountIsRejected(): void
    {
        $account = $this->pendingAccount();
        $account->reject(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        $this->expectException(InvalidAccountStatusTransition::class);

        $account->disable(new \DateTimeImmutable('2026-05-02T12:10:00+00:00'));
    }

    public function testDisablingAlreadyDisabledAccountIsRejectedWithAccountAlreadyDisabled(): void
    {
        $account = $this->approvedRegularAccount();
        $account->disable(new \DateTimeImmutable('2026-05-02T12:10:00+00:00'));

        $this->expectException(AccountAlreadyDisabled::class);

        $account->disable(new \DateTimeImmutable('2026-05-02T12:15:00+00:00'));
    }

    private function approvedRegularAccount(?\DateTimeImmutable $approvedAt = null): Account
    {
        $account = $this->pendingAccount();
        $account->approve($approvedAt ?? new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        return $account;
    }

    private function pendingAccount(): Account
    {
        return Account::register(
            $this->accountId(),
            Email::fromString('registered@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();
    }

    private function accountId(): AccountId
    {
        return AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d');
    }

    private function passwordHash(): PasswordHash
    {
        return PasswordHash::fromString('$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij');
    }
}
