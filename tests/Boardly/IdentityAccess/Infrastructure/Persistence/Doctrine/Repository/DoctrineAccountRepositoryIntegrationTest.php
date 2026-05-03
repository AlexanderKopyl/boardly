<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineAccountRepositoryIntegrationTest extends KernelTestCase
{
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    private EntityManagerInterface $entityManager;
    private DoctrineAccountRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineAccountRepository($this->entityManager, new AccountMapper());

        self::assertTrue(
            $this->entityManager->getConnection()->createSchemaManager()->tablesExist(['accounts']),
            'The accounts table must exist. Run doctrine:migrations:migrate --env=test before this integration test.',
        );

        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM accounts');
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();

        parent::tearDown();
    }

    public function testSaveAndReloadRegisteredPendingAccount(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-03T08:00:00+00:00');
        $account = Account::register(
            $this->accountId('00000001'),
            Email::fromString('pending@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Pending Account'),
            $createdAt,
        )->account();

        $this->repository->save($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($account->id());

        self::assertInstanceOf(Account::class, $stored);
        self::assertSame($account->id()->value(), $stored->id()->value());
        self::assertSame('pending@example.com', $stored->email()->value());
        self::assertSame(self::PASSWORD_HASH, $stored->passwordHash()->value());
        self::assertSame('Pending Account', $stored->name()->value());
        self::assertTrue($stored->status()->isPendingApproval());
        self::assertFalse($stored->isSystemAdmin());
        $this->assertSameInstant($createdAt, $stored->createdAt());
        $this->assertSameInstant($createdAt, $stored->updatedAt());
        self::assertNull($stored->approvedAt());
        self::assertNull($stored->rejectedAt());
        self::assertNull($stored->disabledAt());
    }

    public function testSaveAndReloadActiveSystemAdminAccount(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-03T08:05:00+00:00');
        $account = Account::createSystemAdmin(
            $this->accountId('00000002'),
            Email::fromString('admin@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Admin Account'),
            $createdAt,
        )->account();

        $this->repository->save($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($account->id());

        self::assertInstanceOf(Account::class, $stored);
        self::assertTrue($stored->isSystemAdmin());
        self::assertTrue($stored->status()->isActive());
        self::assertNotNull($stored->approvedAt());
        $this->assertSameInstant($createdAt, $stored->approvedAt());
    }

    public function testFindReturnsNullForMissingAccount(): void
    {
        self::assertNull($this->repository->find($this->accountId('00000003')));
    }

    public function testGetThrowsAccountNotFoundForMissingAccount(): void
    {
        $this->expectException(AccountNotFound::class);

        $this->repository->get($this->accountId('00000004'));
    }

    public function testFindByEmailUsesNormalizedEmailValue(): void
    {
        $account = $this->pendingAccount(
            id: $this->accountId('00000005'),
            email: 'normalized@example.com',
        );

        $this->repository->save($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->findByEmail(Email::fromString('  NORMALIZED@example.com  '));

        self::assertInstanceOf(Account::class, $stored);
        self::assertSame($account->id()->value(), $stored->id()->value());
        self::assertSame('normalized@example.com', $stored->email()->value());
    }

    public function testExistsByEmailReturnsTrueAndFalse(): void
    {
        $this->repository->save($this->pendingAccount(
            id: $this->accountId('00000006'),
            email: 'exists@example.com',
        ));
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertTrue($this->repository->existsByEmail(Email::fromString('EXISTS@example.com')));
        self::assertFalse($this->repository->existsByEmail(Email::fromString('missing@example.com')));
    }

    public function testCountActiveSystemAdminsCountsOnlyActiveSystemAdmins(): void
    {
        $activeSystemAdmin = Account::createSystemAdmin(
            $this->accountId('00000007'),
            Email::fromString('active-admin@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Active Admin'),
            new \DateTimeImmutable('2026-05-03T08:10:00+00:00'),
        )->account();
        $pendingNonAdmin = $this->pendingAccount(
            id: $this->accountId('00000008'),
            email: 'pending-user@example.com',
        );
        $disabledSystemAdmin = Account::createSystemAdmin(
            $this->accountId('00000009'),
            Email::fromString('disabled-admin@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Disabled Admin'),
            new \DateTimeImmutable('2026-05-03T08:11:00+00:00'),
        )->account();
        $disabledSystemAdmin->disable(new \DateTimeImmutable('2026-05-03T08:12:00+00:00'));

        $this->repository->save($activeSystemAdmin);
        $this->repository->save($pendingNonAdmin);
        $this->repository->save($disabledSystemAdmin);
        $this->entityManager->flush();
        $this->entityManager->clear();

        self::assertSame(1, $this->repository->countActiveSystemAdmins());
    }

    public function testLifecycleTimestampsPersistForApprovedRejectedAndDisabledAccounts(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-03T08:20:00+00:00');
        $approvedAccount = $this->pendingAccount(
            id: $this->accountId('00000010'),
            email: 'approved@example.com',
        );
        $approvedAccount->approve($approvedAt);

        $rejectedAt = new \DateTimeImmutable('2026-05-03T08:21:00+00:00');
        $rejectedAccount = $this->pendingAccount(
            id: $this->accountId('00000011'),
            email: 'rejected@example.com',
        );
        $rejectedAccount->reject($rejectedAt);

        $disabledApprovedAt = new \DateTimeImmutable('2026-05-03T08:22:00+00:00');
        $disabledAt = new \DateTimeImmutable('2026-05-03T08:23:00+00:00');
        $disabledAccount = $this->pendingAccount(
            id: $this->accountId('00000012'),
            email: 'disabled@example.com',
        );
        $disabledAccount->approve($disabledApprovedAt);
        $disabledAccount->disable($disabledAt);

        $this->repository->save($approvedAccount);
        $this->repository->save($rejectedAccount);
        $this->repository->save($disabledAccount);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $storedApproved = $this->repository->get($approvedAccount->id());
        $storedRejected = $this->repository->get($rejectedAccount->id());
        $storedDisabled = $this->repository->get($disabledAccount->id());

        self::assertTrue($storedApproved->status()->isActive());
        $this->assertSameInstant($approvedAt, $storedApproved->approvedAt());
        self::assertNull($storedApproved->rejectedAt());
        self::assertNull($storedApproved->disabledAt());

        self::assertTrue($storedRejected->status()->isRejected());
        self::assertNull($storedRejected->approvedAt());
        $this->assertSameInstant($rejectedAt, $storedRejected->rejectedAt());
        self::assertNull($storedRejected->disabledAt());

        self::assertTrue($storedDisabled->status()->isDisabled());
        $this->assertSameInstant($disabledApprovedAt, $storedDisabled->approvedAt());
        self::assertNull($storedDisabled->rejectedAt());
        $this->assertSameInstant($disabledAt, $storedDisabled->disabledAt());
    }

    public function testDuplicateNormalizedEmailFailsAtDatabaseLevel(): void
    {
        $this->repository->save($this->pendingAccount(
            id: $this->accountId('00000013'),
            email: 'duplicate@example.com',
        ));
        $this->entityManager->flush();

        $this->repository->save($this->pendingAccount(
            id: $this->accountId('00000014'),
            email: ' DUPLICATE@example.com ',
        ));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }

    private function pendingAccount(
        AccountId $id,
        string $email,
        string $name = 'Test Account',
        ?\DateTimeImmutable $createdAt = null,
    ): Account {
        return Account::register(
            $id,
            Email::fromString($email),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString($name),
            $createdAt ?? new \DateTimeImmutable('2026-05-03T08:00:00+00:00'),
        )->account();
    }

    private function accountId(string $suffix): AccountId
    {
        return AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-0000'.$suffix);
    }

    private function assertSameInstant(\DateTimeImmutable $expected, ?\DateTimeImmutable $actual): void
    {
        self::assertNotNull($actual);
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());
    }
}
