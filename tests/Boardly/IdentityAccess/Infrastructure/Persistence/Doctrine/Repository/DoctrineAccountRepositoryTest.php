<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DoctrineAccountRepositoryTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testSavePersistsNewEntityWhenAccountDoesNotExist(): void
    {
        $account = $this->pendingAccount(email: 'new@example.com');
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(AccountEntity::class, self::ACCOUNT_ID)
            ->willReturn(null);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (AccountEntity $entity): bool {
                self::assertSame(self::ACCOUNT_ID, $entity->getId());
                self::assertSame('new@example.com', $entity->getEmail());
                self::assertSame('pending_approval', $entity->getStatus());

                return true;
            }));
        $entityManager
            ->expects(self::never())
            ->method('flush');

        $this->repository($entityManager)->save($account);
    }

    public function testSaveUpdatesExistingEntityWhenAccountExists(): void
    {
        $entity = $this->entity(email: 'old@example.com', name: 'Old Account');
        $account = Account::reconstitute(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('updated@example.com'),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Updated Account'),
            AccountStatus::active(),
            true,
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-02T12:05:00+00:00'),
            new \DateTimeImmutable('2026-05-02T12:05:00+00:00'),
            null,
            null,
        );
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(AccountEntity::class, self::ACCOUNT_ID)
            ->willReturn($entity);
        $entityManager
            ->expects(self::never())
            ->method('persist');
        $entityManager
            ->expects(self::never())
            ->method('flush');

        $this->repository($entityManager)->save($account);

        self::assertSame('updated@example.com', $entity->getEmail());
        self::assertSame('Updated Account', $entity->getName());
        self::assertSame('active', $entity->getStatus());
        self::assertTrue($entity->isSystemAdmin());
    }

    public function testGetReturnsAccountWhenFound(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(AccountEntity::class, self::ACCOUNT_ID)
            ->willReturn($this->entity(email: 'found@example.com'));

        $account = $this->repository($entityManager)->get(AccountId::fromString(self::ACCOUNT_ID));

        self::assertSame(self::ACCOUNT_ID, $account->id()->value());
        self::assertSame('found@example.com', $account->email()->value());
    }

    public function testGetThrowsAccountNotFoundWhenMissing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(AccountEntity::class, self::ACCOUNT_ID)
            ->willReturn(null);

        $this->expectException(AccountNotFound::class);

        $this->repository($entityManager)->get(AccountId::fromString(self::ACCOUNT_ID));
    }

    public function testFindReturnsNullWhenMissing(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(AccountEntity::class, self::ACCOUNT_ID)
            ->willReturn(null);

        self::assertNull($this->repository($entityManager)->find(AccountId::fromString(self::ACCOUNT_ID)));
    }

    public function testFindMapsEntityToDomainAccountWhenFound(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('find')
            ->with(AccountEntity::class, self::ACCOUNT_ID)
            ->willReturn($this->entity(email: 'mapped@example.com'));

        $account = $this->repository($entityManager)->find(AccountId::fromString(self::ACCOUNT_ID));

        self::assertInstanceOf(Account::class, $account);
        self::assertSame('mapped@example.com', $account->email()->value());
        self::assertTrue($account->status()->isPendingApproval());
    }

    public function testFindByEmailQueriesUsingNormalizedEmailValue(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'mixed@example.com'])
            ->willReturn($this->entity(email: 'mixed@example.com'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(AccountEntity::class)
            ->willReturn($repository);

        $account = $this->repository($entityManager)->findByEmail(Email::fromString(' MIXED@example.com '));

        self::assertInstanceOf(Account::class, $account);
        self::assertSame('mixed@example.com', $account->email()->value());
    }

    public function testExistsByEmailReturnsTrueWhenCountIsPositive(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($this->countQueryBuilder('3', [
                'select' => 'COUNT(account.id)',
                'fromClass' => AccountEntity::class,
                'fromAlias' => 'account',
                'where' => 'account.email = :email',
                'parameters' => ['email' => 'exists@example.com'],
            ]));

        self::assertTrue($this->repository($entityManager)->existsByEmail(Email::fromString('exists@example.com')));
    }

    public function testExistsByEmailReturnsFalseWhenCountIsZero(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($this->countQueryBuilder('0', [
                'select' => 'COUNT(account.id)',
                'fromClass' => AccountEntity::class,
                'fromAlias' => 'account',
                'where' => 'account.email = :email',
                'parameters' => ['email' => 'missing@example.com'],
            ]));

        self::assertFalse($this->repository($entityManager)->existsByEmail(Email::fromString('missing@example.com')));
    }

    public function testCountActiveSystemAdminsCountsOnlyActiveSystemAdmins(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($this->countQueryBuilder('2', [
                'select' => 'COUNT(account.id)',
                'fromClass' => AccountEntity::class,
                'fromAlias' => 'account',
                'where' => 'account.isSystemAdmin = :isSystemAdmin',
                'andWhere' => 'account.status = :status',
                'parameters' => [
                    'isSystemAdmin' => true,
                    'status' => AccountStatus::active()->value(),
                ],
            ]));

        self::assertSame(2, $this->repository($entityManager)->countActiveSystemAdmins());
    }

    private function repository(EntityManagerInterface $entityManager): DoctrineAccountRepository
    {
        return new DoctrineAccountRepository($entityManager, new AccountMapper());
    }

    private function pendingAccount(string $email = 'account@example.com'): Account
    {
        return Account::register(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString($email),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Test Account'),
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        )->account();
    }

    private function entity(
        string $email = 'account@example.com',
        string $name = 'Stored Account',
    ): AccountEntity {
        return new AccountEntity(
            self::ACCOUNT_ID,
            $email,
            self::PASSWORD_HASH,
            $name,
            'pending_approval',
            false,
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-02T12:01:00+00:00'),
            null,
            null,
            null,
        );
    }

    /**
     * @param array{
     *     select: string,
     *     fromClass: class-string,
     *     fromAlias: string,
     *     where: string,
     *     andWhere?: string,
     *     parameters: array<string, mixed>
     * } $expectations
     *
     * @return QueryBuilder&MockObject
     */
    private function countQueryBuilder(string $count, array $expectations): QueryBuilder
    {
        $query = $this->createMock(Query::class);
        $query
            ->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::once())
            ->method('select')
            ->with($expectations['select'])
            ->willReturnSelf();
        $queryBuilder
            ->expects(self::once())
            ->method('from')
            ->with($expectations['fromClass'], $expectations['fromAlias'])
            ->willReturnSelf();
        $queryBuilder
            ->expects(self::once())
            ->method('where')
            ->with($expectations['where'])
            ->willReturnSelf();

        if (isset($expectations['andWhere'])) {
            $queryBuilder
                ->expects(self::once())
                ->method('andWhere')
                ->with($expectations['andWhere'])
                ->willReturnSelf();
        } else {
            $queryBuilder
                ->expects(self::never())
                ->method('andWhere');
        }

        $queryBuilder
            ->expects(self::exactly(count($expectations['parameters'])))
            ->method('setParameter')
            ->willReturnCallback(static function (string|int $key, mixed $value) use ($expectations, $queryBuilder): QueryBuilder {
                self::assertIsString($key);
                self::assertArrayHasKey($key, $expectations['parameters']);
                self::assertSame($expectations['parameters'][$key], $value);

                return $queryBuilder;
            });

        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        return $queryBuilder;
    }
}
