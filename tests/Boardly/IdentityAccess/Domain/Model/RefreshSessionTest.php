<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Model;

use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class RefreshSessionTest extends TestCase
{
    private const string SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-000000000101';
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-000000000102';
    private const string FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-000000000103';
    private const string TOKEN_HASH = 'sha256:stored-token-hash';

    public function testCreateSessionStoresIdentityHashFamilyExpirationAndCreationTime(): void
    {
        $expiresAt = new \DateTimeImmutable('2026-06-03T12:00:00+00:00');
        $createdAt = new \DateTimeImmutable('2026-05-04T12:00:00+00:00');

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

        self::assertSame(self::SESSION_ID, $session->id()->value());
        self::assertSame(self::ACCOUNT_ID, $session->accountId()->value());
        self::assertSame(self::TOKEN_HASH, $session->tokenHash()->value());
        self::assertSame(self::FAMILY_ID, $session->familyId()->value());
        self::assertSame($expiresAt, $session->expiresAt());
        self::assertSame($createdAt, $session->createdAt());
        self::assertSame('ua-hash', $session->userAgentHash());
        self::assertSame('ip-hash', $session->ipHash());
        self::assertNull($session->revokedAt());
        self::assertNull($session->replacedByTokenId());
        self::assertNull($session->lastUsedAt());
    }

    public function testRawTokenIsNotRepresentedInRefreshSessionApi(): void
    {
        $methodNames = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            (new \ReflectionClass(RefreshSession::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
        );

        self::assertNotContains('rawToken', $methodNames);
        self::assertNotContains('plainRefreshToken', $methodNames);
        self::assertNotContains('refreshToken', $methodNames);
    }

    public function testActiveNonExpiredNonRevokedNonReplacedSessionIsUsable(): void
    {
        $session = $this->session(expiresAt: new \DateTimeImmutable('2026-05-05T12:00:00+00:00'));

        self::assertTrue($session->isUsable(new \DateTimeImmutable('2026-05-04T12:00:00+00:00')));
    }

    public function testExpiredSessionIsNotUsable(): void
    {
        $session = $this->session(expiresAt: new \DateTimeImmutable('2026-05-04T12:00:00+00:00'));

        self::assertTrue($session->isExpired(new \DateTimeImmutable('2026-05-04T12:00:00+00:00')));
        self::assertFalse($session->isUsable(new \DateTimeImmutable('2026-05-04T12:00:00+00:00')));
    }

    public function testRevokedSessionIsNotUsableAndRevokeIsIdempotent(): void
    {
        $session = $this->session();
        $revokedAt = new \DateTimeImmutable('2026-05-04T13:00:00+00:00');

        $session->revoke($revokedAt);
        $session->revoke(new \DateTimeImmutable('2026-05-04T14:00:00+00:00'));

        self::assertTrue($session->isRevoked());
        self::assertSame($revokedAt, $session->revokedAt());
        self::assertFalse($session->isUsable(new \DateTimeImmutable('2026-05-04T13:01:00+00:00')));
    }

    public function testReplacedSessionIsNotUsable(): void
    {
        $session = $this->session();
        $replacedBy = RefreshSessionId::fromString('018f3f7a-9e4c-7b2d-9c52-000000000104');
        $replacedAt = new \DateTimeImmutable('2026-05-04T13:00:00+00:00');

        $session->replaceWith($replacedBy, $replacedAt);

        self::assertTrue($session->wasReplaced());
        self::assertTrue($session->isRevoked());
        self::assertSame($replacedBy, $session->replacedByTokenId());
        self::assertSame($replacedAt, $session->revokedAt());
        self::assertFalse($session->isUsable(new \DateTimeImmutable('2026-05-04T13:01:00+00:00')));
    }

    public function testReplaceWithRejectsReplacingSessionWithItself(): void
    {
        $session = $this->session();

        $this->expectException(\InvalidArgumentException::class);

        $session->replaceWith($session->id(), new \DateTimeImmutable('2026-05-04T13:00:00+00:00'));
    }

    public function testMarkUsedStoresLastUsedTime(): void
    {
        $session = $this->session();
        $usedAt = new \DateTimeImmutable('2026-05-04T13:00:00+00:00');

        $session->markUsed($usedAt);

        self::assertSame($usedAt, $session->lastUsedAt());
    }

    private function session(?\DateTimeImmutable $expiresAt = null): RefreshSession
    {
        return RefreshSession::create(
            RefreshSessionId::fromString(self::SESSION_ID),
            AccountId::fromString(self::ACCOUNT_ID),
            RefreshTokenHash::fromString(self::TOKEN_HASH),
            RefreshSessionFamilyId::fromString(self::FAMILY_ID),
            $expiresAt ?? new \DateTimeImmutable('2026-06-03T12:00:00+00:00'),
            new \DateTimeImmutable('2026-05-04T12:00:00+00:00'),
        );
    }
}
