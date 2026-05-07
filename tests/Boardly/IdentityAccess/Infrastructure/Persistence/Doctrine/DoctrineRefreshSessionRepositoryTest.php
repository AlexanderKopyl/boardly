<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\RefreshSessionEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\RefreshSessionMapper;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository\DoctrineRefreshSessionRepository;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineRefreshSessionRepositoryTest extends KernelTestCase
{
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';
    private const string FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-000000000401';
    private const string OTHER_FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-000000000402';

    private EntityManagerInterface $entityManager;
    private DoctrineRefreshSessionRepository $repository;
    private DoctrineAccountRepository $accountRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineRefreshSessionRepository($this->entityManager, new RefreshSessionMapper());
        $this->accountRepository = new DoctrineAccountRepository($this->entityManager, new AccountMapper());

        self::assertTrue(
            $this->entityManager->getConnection()->createSchemaManager()->tablesExist(['accounts', 'refresh_sessions']),
            'The accounts and refresh_sessions tables must exist. Run doctrine:migrations:migrate --env=test before this test.',
        );

        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM refresh_sessions');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM accounts');
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testSaveAndFindById(): void
    {
        $account = $this->persistAccount('000000000501', 'session-owner@example.com');
        $session = $this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000601',
            accountId: $account->id(),
            tokenHash: 'sha256:find-by-id',
        );

        $this->repository->save($session);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($session->id());

        self::assertInstanceOf(RefreshSession::class, $stored);
        self::assertSame($session->id()->value(), $stored->id()->value());
        self::assertSame($account->id()->value(), $stored->accountId()->value());
        self::assertSame('sha256:find-by-id', $stored->tokenHash()->value());
        self::assertTrue($stored->isUsable(new \DateTimeImmutable('2026-05-04T12:30:00+00:00')));
    }

    public function testSaveUpdatesExistingSession(): void
    {
        $account = $this->persistAccount('000000000502', 'update-session@example.com');
        $session = $this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000602',
            accountId: $account->id(),
            tokenHash: 'sha256:update-existing',
        );

        $this->repository->save($session);
        $this->entityManager->flush();

        $usedAt = new \DateTimeImmutable('2026-05-04T13:00:00+00:00');
        $session->markUsed($usedAt);
        $this->repository->save($session);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($session->id());

        self::assertInstanceOf(RefreshSession::class, $stored);
        $this->assertSameInstant($usedAt, $stored->lastUsedAt());
    }

    public function testSaveAndFindByTokenHash(): void
    {
        $account = $this->persistAccount('000000000503', 'hash-owner@example.com');
        $session = $this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000603',
            accountId: $account->id(),
            tokenHash: 'sha256:find-by-hash',
        );

        $this->repository->save($session);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->findByTokenHash(RefreshTokenHash::fromString('sha256:find-by-hash'));

        self::assertInstanceOf(RefreshSession::class, $stored);
        self::assertSame($session->id()->value(), $stored->id()->value());
    }

    public function testUnknownTokenHashReturnsNull(): void
    {
        self::assertNull($this->repository->findByTokenHash(RefreshTokenHash::fromString('sha256:missing')));
    }

    public function testDuplicateTokenHashFailsAtDatabaseLevel(): void
    {
        $account = $this->persistAccount('000000000504', 'duplicate-hash@example.com');

        $this->repository->save($this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000604',
            accountId: $account->id(),
            tokenHash: 'sha256:duplicate',
        ));
        $this->repository->save($this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000605',
            accountId: $account->id(),
            tokenHash: 'sha256:duplicate',
        ));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->entityManager->flush();
    }

    public function testRevokeFamilyRevokesAllSessionsInFamilyOnly(): void
    {
        $account = $this->persistAccount('000000000505', 'family-owner@example.com');
        $first = $this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000606',
            accountId: $account->id(),
            tokenHash: 'sha256:first-family',
            familyId: self::FAMILY_ID,
        );
        $second = $this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000607',
            accountId: $account->id(),
            tokenHash: 'sha256:second-family',
            familyId: self::FAMILY_ID,
        );
        $otherFamily = $this->session(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000608',
            accountId: $account->id(),
            tokenHash: 'sha256:other-family',
            familyId: self::OTHER_FAMILY_ID,
        );

        $this->repository->save($first);
        $this->repository->save($second);
        $this->repository->save($otherFamily);
        $this->entityManager->flush();

        $revokedAt = new \DateTimeImmutable('2026-05-04T14:00:00+00:00');
        $this->repository->revokeFamily(RefreshSessionFamilyId::fromString(self::FAMILY_ID), $revokedAt);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $storedFirst = $this->repository->find($first->id());
        $storedSecond = $this->repository->find($second->id());
        $storedOtherFamily = $this->repository->find($otherFamily->id());

        self::assertInstanceOf(RefreshSession::class, $storedFirst);
        self::assertInstanceOf(RefreshSession::class, $storedSecond);
        self::assertInstanceOf(RefreshSession::class, $storedOtherFamily);
        $this->assertSameInstant($revokedAt, $storedFirst->revokedAt());
        $this->assertSameInstant($revokedAt, $storedSecond->revokedAt());
        self::assertNull($storedOtherFamily->revokedAt());
    }

    public function testNoRawTokenColumnOrFieldIsStored(): void
    {
        $columns = $this->entityManager
            ->getConnection()
            ->createSchemaManager()
            ->listTableColumns('refresh_sessions');

        self::assertArrayHasKey('token_hash', $columns);
        self::assertArrayNotHasKey('raw_token', $columns);
        self::assertArrayNotHasKey('refresh_token', $columns);

        $properties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            (new \ReflectionClass(RefreshSessionEntity::class))->getProperties(),
        );

        self::assertContains('tokenHash', $properties);
        self::assertNotContains('rawToken', $properties);
        self::assertNotContains('refreshToken', $properties);
    }

    private function persistAccount(string $idSuffix, string $email): Account
    {
        $account = Account::createSystemAdmin(
            AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-'.$idSuffix),
            Email::fromString($email),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Session Owner'),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
        )->account();

        $this->accountRepository->save($account);
        $this->entityManager->flush();

        return $account;
    }

    private function session(
        string $id,
        AccountId $accountId,
        string $tokenHash,
        string $familyId = self::FAMILY_ID,
    ): RefreshSession {
        return RefreshSession::create(
            RefreshSessionId::fromString($id),
            $accountId,
            RefreshTokenHash::fromString($tokenHash),
            RefreshSessionFamilyId::fromString($familyId),
            new \DateTimeImmutable('2026-06-03T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
            'ua-hash',
            'ip-hash',
        );
    }

    private function assertSameInstant(\DateTimeImmutable $expected, ?\DateTimeImmutable $actual): void
    {
        self::assertNotNull($actual);
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());
    }
}
