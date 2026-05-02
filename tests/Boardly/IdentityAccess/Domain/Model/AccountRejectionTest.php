<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Event\AccountRejected;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyRejected;
use App\Boardly\IdentityAccess\Domain\Exception\AccountNotPendingApproval;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountRejectionTest extends TestCase
{
    public function testPendingAccountCanBeRejected(): void
    {
        $account = $this->pendingAccount();

        $account->reject(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        self::assertTrue($account->status()->isRejected());
    }

    public function testRejectSetsRejectedAtAndUpdatesUpdatedAt(): void
    {
        $account = $this->pendingAccount();
        $rejectedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');

        $account->reject($rejectedAt);

        self::assertSame($rejectedAt, $account->rejectedAt());
        self::assertSame($rejectedAt, $account->updatedAt());
    }

    public function testRejectKeepsApprovedAtAndDisabledAtNull(): void
    {
        $account = $this->pendingAccount();

        $account->reject(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        self::assertNull($account->approvedAt());
        self::assertNull($account->disabledAt());
    }

    public function testRejectReturnsAccountRejectedEvent(): void
    {
        $id = $this->accountId();
        $rejectedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $account = Account::register(
            $id,
            Email::fromString('registered@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        $event = $account->reject($rejectedAt)->event();

        self::assertInstanceOf(AccountRejected::class, $event);
        self::assertSame($id, $event->accountId());
        self::assertSame($rejectedAt, $event->rejectedAt());
    }

    public function testRejectingActiveSystemAdminIsRejected(): void
    {
        $account = Account::createSystemAdmin(
            $this->accountId(),
            Email::fromString('admin@example.com'),
            $this->passwordHash(),
            AccountName::fromString('System Admin'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        $this->expectException(AccountNotPendingApproval::class);

        $account->reject(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));
    }

    public function testRejectingAlreadyRejectedAccountIsRejected(): void
    {
        $account = $this->pendingAccount();
        $account->reject(new \DateTimeImmutable('2026-05-02T12:05:00+00:00'));

        $this->expectException(AccountAlreadyRejected::class);

        $account->reject(new \DateTimeImmutable('2026-05-02T12:10:00+00:00'));
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
