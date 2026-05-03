<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper;

use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountLifecycleState;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidAccountStatus;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Exception\AccountMappingFailed;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountMapperTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string OTHER_ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e';
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testMapsRegisteredPendingAccountToAccountEntity(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');
        $account = Account::register(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('pending@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Pending Account'),
            $createdAt,
        )->account();

        $entity = $this->mapper()->toEntity($account);

        self::assertSame(self::ACCOUNT_ID, $entity->getId());
        self::assertSame('pending@example.com', $entity->getEmail());
        self::assertSame(self::PASSWORD_HASH, $entity->getPasswordHash());
        self::assertSame('Pending Account', $entity->getName());
        self::assertSame('pending_approval', $entity->getStatus());
        self::assertFalse($entity->isSystemAdmin());
        self::assertSame($createdAt, $entity->getCreatedAt());
        self::assertSame($createdAt, $entity->getUpdatedAt());
        self::assertNull($entity->getApprovedAt());
        self::assertNull($entity->getRejectedAt());
        self::assertNull($entity->getDisabledAt());
    }

    public function testMapsActiveSystemAdminAccountToAccountEntity(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');
        $account = Account::createSystemAdmin(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('admin@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Admin Account'),
            $createdAt,
        )->account();

        $entity = $this->mapper()->toEntity($account);

        self::assertSame(self::ACCOUNT_ID, $entity->getId());
        self::assertSame('admin@example.com', $entity->getEmail());
        self::assertSame(self::PASSWORD_HASH, $entity->getPasswordHash());
        self::assertSame('Admin Account', $entity->getName());
        self::assertSame('active', $entity->getStatus());
        self::assertTrue($entity->isSystemAdmin());
        self::assertSame($createdAt, $entity->getCreatedAt());
        self::assertSame($createdAt, $entity->getUpdatedAt());
        self::assertSame($createdAt, $entity->getApprovedAt());
        self::assertNull($entity->getRejectedAt());
        self::assertNull($entity->getDisabledAt());
    }

    public function testUpdateEntityUpdatesMutableScalarFieldsButKeepsIdAndCreatedAtUnchanged(): void
    {
        $originalCreatedAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');
        $updatedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:06:00+00:00');
        $entity = $this->entity(
            id: self::ACCOUNT_ID,
            email: 'old@example.com',
            passwordHash: self::PASSWORD_HASH,
            name: 'Old Account',
            status: 'pending_approval',
            isSystemAdmin: false,
            createdAt: $originalCreatedAt,
            updatedAt: new \DateTimeImmutable('2026-05-02T12:01:00+00:00'),
        );
        $account = Account::reconstitute(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('new@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('New Account'),
            AccountStatus::active(),
            true,
            new \DateTimeImmutable('2026-05-02T13:00:00+00:00'),
            $updatedAt,
            $approvedAt,
            null,
            null,
        );

        $this->mapper()->updateEntity($account, $entity);

        self::assertSame(self::ACCOUNT_ID, $entity->getId());
        self::assertSame($originalCreatedAt, $entity->getCreatedAt());
        self::assertSame('new@example.com', $entity->getEmail());
        self::assertSame(self::PASSWORD_HASH, $entity->getPasswordHash());
        self::assertSame('New Account', $entity->getName());
        self::assertSame('active', $entity->getStatus());
        self::assertTrue($entity->isSystemAdmin());
        self::assertSame($updatedAt, $entity->getUpdatedAt());
        self::assertSame($approvedAt, $entity->getApprovedAt());
        self::assertNull($entity->getRejectedAt());
        self::assertNull($entity->getDisabledAt());
    }

    public function testUpdateEntityRejectsMismatchedIdsAndLeavesEntityUnchanged(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');
        $updatedAt = new \DateTimeImmutable('2026-05-02T12:01:00+00:00');
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:02:00+00:00');
        $rejectedAt = new \DateTimeImmutable('2026-05-02T12:03:00+00:00');
        $disabledAt = new \DateTimeImmutable('2026-05-02T12:04:00+00:00');
        $entity = $this->entity(
            id: self::ACCOUNT_ID,
            email: 'old@example.com',
            passwordHash: self::PASSWORD_HASH,
            name: 'Old Account',
            status: 'disabled',
            isSystemAdmin: false,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            approvedAt: $approvedAt,
            rejectedAt: $rejectedAt,
            disabledAt: $disabledAt,
        );
        $account = Account::reconstitute(
            AccountId::fromString(self::OTHER_ACCOUNT_ID),
            Email::fromString('new@example.com'),
            PasswordHash::fromString('$2y$13$AbCdEfGhIjKlMnOpQrStUu.DcBa9876543210abcdefABCDEFghij'),
            AccountName::fromString('New Account'),
            AccountStatus::active(),
            true,
            new \DateTimeImmutable('2026-05-02T13:00:00+00:00'),
            new \DateTimeImmutable('2026-05-02T13:01:00+00:00'),
            new \DateTimeImmutable('2026-05-02T13:02:00+00:00'),
            null,
            null,
        );

        try {
            $this->mapper()->updateEntity($account, $entity);
            self::fail('Expected account mapping failure for mismatched account ids.');
        } catch (AccountMappingFailed $exception) {
            self::assertStringContainsString(self::OTHER_ACCOUNT_ID, $exception->getMessage());
            self::assertStringContainsString(self::ACCOUNT_ID, $exception->getMessage());
        }

        self::assertSame(self::ACCOUNT_ID, $entity->getId());
        self::assertSame('old@example.com', $entity->getEmail());
        self::assertSame(self::PASSWORD_HASH, $entity->getPasswordHash());
        self::assertSame('Old Account', $entity->getName());
        self::assertSame('disabled', $entity->getStatus());
        self::assertFalse($entity->isSystemAdmin());
        self::assertSame($createdAt, $entity->getCreatedAt());
        self::assertSame($updatedAt, $entity->getUpdatedAt());
        self::assertSame($approvedAt, $entity->getApprovedAt());
        self::assertSame($rejectedAt, $entity->getRejectedAt());
        self::assertSame($disabledAt, $entity->getDisabledAt());
    }

    public function testMapsAccountEntityBackToPendingAccount(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');
        $updatedAt = new \DateTimeImmutable('2026-05-02T12:01:00+00:00');
        $account = $this->mapper()->toDomain($this->entity(
            status: 'pending_approval',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        ));

        self::assertSame(self::ACCOUNT_ID, $account->id()->value());
        self::assertSame('account@example.com', $account->email()->value());
        self::assertSame(self::PASSWORD_HASH, $account->passwordHash()->value());
        self::assertSame('Stored Account', $account->name()->value());
        self::assertTrue($account->status()->isPendingApproval());
        self::assertFalse($account->isSystemAdmin());
        self::assertSame($createdAt, $account->createdAt());
        self::assertSame($updatedAt, $account->updatedAt());
        self::assertNull($account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testMapsAccountEntityBackToActiveAccount(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $account = $this->mapper()->toDomain($this->entity(
            status: 'active',
            isSystemAdmin: true,
            approvedAt: $approvedAt,
        ));

        self::assertTrue($account->status()->isActive());
        self::assertTrue($account->isSystemAdmin());
        self::assertSame($approvedAt, $account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testMapsAccountEntityBackToRejectedAccount(): void
    {
        $rejectedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $account = $this->mapper()->toDomain($this->entity(
            status: 'rejected',
            rejectedAt: $rejectedAt,
        ));

        self::assertTrue($account->status()->isRejected());
        self::assertFalse($account->isSystemAdmin());
        self::assertNull($account->approvedAt());
        self::assertSame($rejectedAt, $account->rejectedAt());
        self::assertNull($account->disabledAt());
    }

    public function testMapsAccountEntityBackToDisabledAccount(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:05:00+00:00');
        $disabledAt = new \DateTimeImmutable('2026-05-02T12:10:00+00:00');
        $account = $this->mapper()->toDomain($this->entity(
            status: 'disabled',
            approvedAt: $approvedAt,
            disabledAt: $disabledAt,
        ));

        self::assertTrue($account->status()->isDisabled());
        self::assertSame($approvedAt, $account->approvedAt());
        self::assertNull($account->rejectedAt());
        self::assertSame($disabledAt, $account->disabledAt());
    }

    public function testInvalidEntityStatusFailsDuringToDomain(): void
    {
        $this->expectException(InvalidAccountStatus::class);

        $this->mapper()->toDomain($this->entity(status: 'archived'));
    }

    public function testInvalidEntityLifecycleTimestampsFailDuringToDomain(): void
    {
        $this->expectException(InvalidAccountLifecycleState::class);

        $this->mapper()->toDomain($this->entity(
            status: 'pending_approval',
            approvedAt: new \DateTimeImmutable('2026-05-02T12:05:00+00:00'),
        ));
    }

    public function testMapperDoesNotExposeDoctrineTypesToDomainAccount(): void
    {
        $method = new \ReflectionMethod(AccountMapper::class, 'toDomain');
        $account = $this->mapper()->toDomain($this->entity());

        self::assertSame(Account::class, (string) $method->getReturnType());
        self::assertSame(self::ACCOUNT_ID, $account->id()->value());
    }

    private function mapper(): AccountMapper
    {
        return new AccountMapper();
    }

    private function entity(
        string $id = self::ACCOUNT_ID,
        string $email = 'account@example.com',
        string $passwordHash = self::PASSWORD_HASH,
        string $name = 'Stored Account',
        string $status = 'pending_approval',
        bool $isSystemAdmin = false,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        ?\DateTimeImmutable $approvedAt = null,
        ?\DateTimeImmutable $rejectedAt = null,
        ?\DateTimeImmutable $disabledAt = null,
    ): AccountEntity {
        return new AccountEntity(
            $id,
            $email,
            $passwordHash,
            $name,
            $status,
            $isSystemAdmin,
            $createdAt ?? new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
            $updatedAt ?? new \DateTimeImmutable('2026-05-02T12:01:00+00:00'),
            $approvedAt,
            $rejectedAt,
            $disabledAt,
        );
    }
}
