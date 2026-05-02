<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyActive;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountApprovalTest extends TestCase
{
    public function testPendingAccountCanBeApproved(): void
    {
        $account = $this->pendingAccount();

        $account->approve(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        self::assertTrue($account->status()->isActive());
    }

    public function testApproveSetsApprovedAtAndUpdatesUpdatedAt(): void
    {
        $account = $this->pendingAccount();
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');

        $account->approve($approvedAt);

        self::assertSame($approvedAt, $account->approvedAt());
        self::assertSame($approvedAt, $account->updatedAt());
    }

    public function testApproveKeepsRejectedAtAndDisabledAtNull(): void
    {
        $account = $this->pendingAccount();

        $account->approve(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testApproveReturnsAccountApprovedEvent(): void
    {
        $id = $this->accountId();
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $account = Account::register(
            $id,
            Email::fromString('registered@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        $event = $account->approve($approvedAt)->event();

        self::assertInstanceOf(AccountApproved::class, $event);
        self::assertSame($id, $event->accountId());
        self::assertSame($approvedAt, $event->approvedAt());
    }

    public function testApprovingAlreadyActiveSystemAdminIsRejected(): void
    {
        $account = Account::createSystemAdmin(
            $this->accountId(),
            Email::fromString('admin@example.com'),
            $this->passwordHash(),
            AccountName::fromString('System Admin'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        $this->expectException(AccountAlreadyActive::class);

        $account->approve(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));
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
