<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine;

use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\RefreshSessionEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\RefreshSessionMapper;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class RefreshSessionMapperTest extends TestCase
{
    private const string SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-000000000201';
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-000000000202';
    private const string FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-000000000203';
    private const string REPLACEMENT_ID = '018f3f7a-9e4c-7b2d-9c52-000000000204';
    private const string TOKEN_HASH = 'sha256:mapper-token-hash';

    public function testMapsDomainToEntity(): void
    {
        $createdAt = new \DateTimeImmutable('2026-05-04T12:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2026-06-03T12:00:00+00:00');
        $lastUsedAt = new \DateTimeImmutable('2026-05-04T12:30:00+00:00');
        $session = RefreshSession::create(
            RefreshSessionId::fromString(self::SESSION_ID),
            AccountId::fromString(self::ACCOUNT_ID),
            RefreshTokenHash::fromString(self::TOKEN_HASH),
            RefreshSessionFamilyId::fromString(self::FAMILY_ID),
            $expiresAt,
            $createdAt,
            'ua-hash',
            'ip-hash',
        );
        $session->markUsed($lastUsedAt);

        $entity = $this->mapper()->toEntity($session, $this->accountEntity());

        self::assertSame(self::SESSION_ID, $entity->getId());
        self::assertSame(self::ACCOUNT_ID, $entity->getAccountId());
        self::assertSame(self::TOKEN_HASH, $entity->getTokenHash());
        self::assertSame(self::FAMILY_ID, $entity->getFamilyId());
        self::assertSame($expiresAt, $entity->getExpiresAt());
        self::assertSame($createdAt, $entity->getCreatedAt());
        self::assertSame($lastUsedAt, $entity->getLastUsedAt());
        self::assertSame('ua-hash', $entity->getUserAgentHash());
        self::assertSame('ip-hash', $entity->getIpHash());
        self::assertNull($entity->getRevokedAt());
        self::assertNull($entity->getReplacedByTokenId());
    }

    public function testMapsEntityToDomain(): void
    {
        $revokedAt = new \DateTimeImmutable('2026-05-04T13:00:00+00:00');
        $lastUsedAt = new \DateTimeImmutable('2026-05-04T12:30:00+00:00');

        $session = $this->mapper()->toDomain($this->entity(
            revokedAt: $revokedAt,
            replacedByTokenId: self::REPLACEMENT_ID,
            lastUsedAt: $lastUsedAt,
            userAgentHash: 'ua-hash',
            ipHash: 'ip-hash',
        ));

        self::assertSame(self::SESSION_ID, $session->id()->value());
        self::assertSame(self::ACCOUNT_ID, $session->accountId()->value());
        self::assertSame(self::TOKEN_HASH, $session->tokenHash()->value());
        self::assertSame(self::FAMILY_ID, $session->familyId()->value());
        self::assertSame($revokedAt, $session->revokedAt());
        self::assertSame(self::REPLACEMENT_ID, $session->replacedByTokenId()?->value());
        self::assertSame($lastUsedAt, $session->lastUsedAt());
        self::assertSame('ua-hash', $session->userAgentHash());
        self::assertSame('ip-hash', $session->ipHash());
    }

    public function testPreservesNullableFields(): void
    {
        $session = $this->mapper()->toDomain($this->entity());
        $entity = $this->mapper()->toEntity($session, $this->accountEntity());

        self::assertNull($session->revokedAt());
        self::assertNull($session->replacedByTokenId());
        self::assertNull($session->lastUsedAt());
        self::assertNull($session->userAgentHash());
        self::assertNull($session->ipHash());
        self::assertNull($entity->getRevokedAt());
        self::assertNull($entity->getReplacedByTokenId());
        self::assertNull($entity->getLastUsedAt());
        self::assertNull($entity->getUserAgentHash());
        self::assertNull($entity->getIpHash());
    }

    public function testUpdateEntityUpdatesMutableFields(): void
    {
        $entity = $this->entity(tokenHash: 'sha256:old-hash');
        $session = RefreshSession::reconstitute(
            RefreshSessionId::fromString(self::SESSION_ID),
            AccountId::fromString(self::ACCOUNT_ID),
            RefreshTokenHash::fromString('sha256:new-hash'),
            RefreshSessionFamilyId::fromString(self::FAMILY_ID),
            new \DateTimeImmutable('2026-06-04T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T13:00:00+00:00'),
            RefreshSessionId::fromString(self::REPLACEMENT_ID),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T12:30:00+00:00'),
            'new-ua-hash',
            'new-ip-hash',
        );

        $this->mapper()->updateEntity($session, $entity);

        self::assertSame('sha256:new-hash', $entity->getTokenHash());
        self::assertSame(self::REPLACEMENT_ID, $entity->getReplacedByTokenId());
        self::assertSame('new-ua-hash', $entity->getUserAgentHash());
        self::assertSame('new-ip-hash', $entity->getIpHash());
    }

    public function testMapperDoesNotExposeRawTokenApi(): void
    {
        $methodNames = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            (new \ReflectionClass(RefreshSessionEntity::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
        );

        self::assertNotContains('getRawToken', $methodNames);
        self::assertNotContains('getRefreshToken', $methodNames);
    }

    private function mapper(): RefreshSessionMapper
    {
        return new RefreshSessionMapper();
    }

    private function entity(
        string $id = self::SESSION_ID,
        string $accountId = self::ACCOUNT_ID,
        string $tokenHash = self::TOKEN_HASH,
        string $familyId = self::FAMILY_ID,
        ?\DateTimeImmutable $expiresAt = null,
        ?\DateTimeImmutable $revokedAt = null,
        ?string $replacedByTokenId = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $lastUsedAt = null,
        ?string $userAgentHash = null,
        ?string $ipHash = null,
    ): RefreshSessionEntity {
        return new RefreshSessionEntity(
            $id,
            $this->accountEntity($accountId),
            $tokenHash,
            $familyId,
            $expiresAt ?? new \DateTimeImmutable('2026-06-03T12:00:00+00:00'),
            $revokedAt,
            $replacedByTokenId,
            $createdAt ?? new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
            $lastUsedAt,
            $userAgentHash,
            $ipHash,
        );
    }

    private function accountEntity(string $accountId = self::ACCOUNT_ID): AccountEntity
    {
        return new AccountEntity(
            $accountId,
            'account@example.com',
            '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij',
            'Stored Account',
            'active',
            true,
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
            null,
            null,
        );
    }
}
