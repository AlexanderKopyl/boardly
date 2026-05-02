<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountCreationTest extends TestCase
{
    public function testRegisterCreatesPendingNonSystemAdminAccount(): void
    {
        $id = $this->accountId();
        $email = Email::fromString('registered@example.com');
        $passwordHash = $this->passwordHash();
        $name = AccountName::fromString('Registered Account');
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $result = Account::register($id, $email, $passwordHash, $name, $createdAt);
        $account = $result->account();

        self::assertSame($id, $account->id());
        self::assertSame($email, $account->email());
        self::assertSame($passwordHash, $account->passwordHash());
        self::assertSame($name, $account->name());
        self::assertTrue($account->status()->isPendingApproval());
        self::assertFalse($account->isSystemAdmin());
    }

    public function testRegisterTimestampsAreCorrect(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $account = Account::register(
            $this->accountId(),
            Email::fromString('registered@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            $createdAt,
        )->account();

        self::assertSame($createdAt, $account->createdAt());
        self::assertSame($createdAt, $account->updatedAt());
    }

    public function testRegisterNullableLifecycleTimestampsAreNull(): void
    {
        $account = Account::register(
            $this->accountId(),
            Email::fromString('registered@example.com'),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        self::assertNull($account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testRegisterReturnsAccountRegisteredEvent(): void
    {
        $id = $this->accountId();
        $email = Email::fromString('registered@example.com');
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $event = Account::register(
            $id,
            $email,
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            $createdAt,
        )->event();

        self::assertInstanceOf(AccountRegistered::class, $event);
        self::assertSame($id, $event->accountId());
        self::assertSame($email, $event->email());
        self::assertFalse($event->isSystemAdmin());
        self::assertSame($createdAt, $event->registeredAt());
    }

    public function testCreateSystemAdminCreatesActiveSystemAdminAccount(): void
    {
        $id = $this->accountId();
        $email = Email::fromString('admin@example.com');
        $passwordHash = $this->passwordHash();
        $name = AccountName::fromString('System Admin');
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $result = Account::createSystemAdmin($id, $email, $passwordHash, $name, $createdAt);
        $account = $result->account();

        self::assertSame($id, $account->id());
        self::assertSame($email, $account->email());
        self::assertSame($passwordHash, $account->passwordHash());
        self::assertSame($name, $account->name());
        self::assertTrue($account->status()->isActive());
        self::assertTrue($account->isSystemAdmin());
        self::assertSame($createdAt, $account->createdAt());
        self::assertSame($createdAt, $account->updatedAt());
    }

    public function testCreateSystemAdminSetsApprovedAtToCreationTime(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $account = Account::createSystemAdmin(
            $this->accountId(),
            Email::fromString('admin@example.com'),
            $this->passwordHash(),
            AccountName::fromString('System Admin'),
            $createdAt,
        )->account();

        self::assertSame($createdAt, $account->approvedAt());
    }

    public function testCreateSystemAdminRejectedAtAndDisabledAtAreNull(): void
    {
        $account = Account::createSystemAdmin(
            $this->accountId(),
            Email::fromString('admin@example.com'),
            $this->passwordHash(),
            AccountName::fromString('System Admin'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();

        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testCreateSystemAdminReturnsAccountRegisteredEvent(): void
    {
        $id = $this->accountId();
        $email = Email::fromString('admin@example.com');
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $event = Account::createSystemAdmin(
            $id,
            $email,
            $this->passwordHash(),
            AccountName::fromString('System Admin'),
            $createdAt,
        )->event();

        self::assertInstanceOf(AccountRegistered::class, $event);
        self::assertSame($id, $event->accountId());
        self::assertSame($email, $event->email());
        self::assertTrue($event->isSystemAdmin());
        self::assertSame($createdAt, $event->registeredAt());
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
