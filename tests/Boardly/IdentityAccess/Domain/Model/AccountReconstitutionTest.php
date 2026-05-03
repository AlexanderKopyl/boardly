<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountLifecycleState;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountReconstitutionTest extends TestCase
{
    public function testReconstitutesPendingAccount(): void
    {
        $id = $this->accountId();
        $email = Email::fromString('pending@example.com');
        $passwordHash = $this->passwordHash();
        $name = AccountName::fromString('Pending Account');
        $status = AccountStatus::pendingApproval();
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');
        $updatedAt = new \DateTimeImmutable('2026-05-02T12:01:00+00:00');

        $account = Account::reconstitute(
            $id,
            $email,
            $passwordHash,
            $name,
            $status,
            false,
            $createdAt,
            $updatedAt,
            null,
            null,
            null,
        );

        self::assertSame($id, $account->id());
        self::assertSame($email, $account->email());
        self::assertSame($passwordHash, $account->passwordHash());
        self::assertSame($name, $account->name());
        self::assertSame($status, $account->status());
        self::assertFalse($account->isSystemAdmin());
        self::assertSame($createdAt, $account->createdAt());
        self::assertSame($updatedAt, $account->updatedAt());
        self::assertNull($account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testReconstitutesActiveAccount(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');

        $account = $this->reconstitute(
            AccountStatus::active(),
            true,
            $approvedAt,
            null,
            null,
        );

        self::assertTrue($account->status()->isActive());
        self::assertTrue($account->isSystemAdmin());
        self::assertSame($approvedAt, $account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testReconstitutesRejectedAccount(): void
    {
        $rejectedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');

        $account = $this->reconstitute(
            AccountStatus::rejected(),
            false,
            null,
            $rejectedAt,
            null,
        );

        self::assertTrue($account->status()->isRejected());
        self::assertFalse($account->isSystemAdmin());
        self::assertNull($account->approvedAt());
        self::assertSame($rejectedAt, $account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testReconstitutesDisabledAccount(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $disabledAt = new \DateTimeImmutable('2026-05-02T12:10:00+00:00');

        $account = $this->reconstitute(
            AccountStatus::disabled(),
            false,
            $approvedAt,
            null,
            $disabledAt,
        );

        self::assertTrue($account->status()->isDisabled());
        self::assertSame($approvedAt, $account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertSame($disabledAt, $account->disabledAt());
    }

    public function testReconstituteReturnsAccountAndDoesNotReturnDomainEvents(): void
    {
        $account = $this->reconstitute(
            AccountStatus::pendingApproval(),
            false,
            null,
            null,
            null,
        );

        self::assertInstanceOf(Account::class, $account);
        self::assertFalse(method_exists($account, 'event'));
    }

    public function testRejectsPendingAccountWithLifecycleTimestamps(): void
    {
        $this->expectException(InvalidAccountLifecycleState::class);

        $this->reconstitute(
            AccountStatus::pendingApproval(),
            false,
            new \DateTimeImmutable('2026-05-02T12:05:00+00:00'),
            null,
            null,
        );
    }

    public function testRejectsActiveAccountWithoutApprovedAt(): void
    {
        $this->expectException(InvalidAccountLifecycleState::class);

        $this->reconstitute(AccountStatus::active(), false, null, null, null);
    }

    public function testRejectsRejectedAccountWithoutRejectedAt(): void
    {
        $this->expectException(InvalidAccountLifecycleState::class);

        $this->reconstitute(AccountStatus::rejected(), false, null, null, null);
    }

    public function testRejectsDisabledAccountWithoutDisabledAt(): void
    {
        $this->expectException(InvalidAccountLifecycleState::class);

        $this->reconstitute(
            AccountStatus::disabled(),
            false,
            new \DateTimeImmutable('2026-05-02T12:05:00+00:00'),
            null,
            null,
        );
    }

    public function testRejectsDisabledAccountWithRejectedAt(): void
    {
        $this->expectException(InvalidAccountLifecycleState::class);

        $this->reconstitute(
            AccountStatus::disabled(),
            false,
            new \DateTimeImmutable('2026-05-02T12:05:00+00:00'),
            new \DateTimeImmutable('2026-05-02T12:07:00+00:00'),
            new \DateTimeImmutable('2026-05-02T12:10:00+00:00'),
        );
    }

    private function reconstitute(
        AccountStatus $status,
        bool $isSystemAdmin,
        ?\DateTimeImmutable $approvedAt,
        ?\DateTimeImmutable $rejectedAt,
        ?\DateTimeImmutable $disabledAt,
    ): Account {
        return Account::reconstitute(
            $this->accountId(),
            Email::fromString('account@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Stored Account'),
            $status,
            $isSystemAdmin,
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-02T12:15:00+00:00'),
            $approvedAt,
            $rejectedAt,
            $disabledAt,
        );
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
