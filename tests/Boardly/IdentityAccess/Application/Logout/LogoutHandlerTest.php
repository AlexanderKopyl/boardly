<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Application\Logout;

use App\Boardly\IdentityAccess\Application\Logout\LogoutCommand;
use App\Boardly\IdentityAccess\Application\Logout\LogoutHandler;
use App\Boardly\IdentityAccess\Application\Logout\LogoutResult;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\RefreshSession\RefreshSessionRevoker;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LogoutHandlerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string CURRENT_SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e';
    private const string OTHER_SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f';
    private const string FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c30';

    public function testMissingNullTokenReturnsSuccess(): void
    {
        $refreshTokenHasher = new FakeRefreshTokenHasher();
        $refreshSessions = new FakeRefreshSessionRepository();
        $transactional = new FakeTransactional();

        $result = $this->handler(
            refreshTokenHasher: $refreshTokenHasher,
            refreshSessions: $refreshSessions,
            transactional: $transactional,
        )->__invoke(new LogoutCommand(null));

        self::assertInstanceOf(LogoutResult::class, $result);
        self::assertSame([], $refreshTokenHasher->receivedRawRefreshTokens);
        self::assertSame([], $refreshSessions->lookedUpTokenHashes);
        self::assertSame([], $refreshSessions->savedSessions);
        self::assertSame(0, $transactional->transactionCallCount);
    }

    public function testEmptyTokenReturnsSuccess(): void
    {
        $refreshTokenHasher = new FakeRefreshTokenHasher();
        $refreshSessions = new FakeRefreshSessionRepository();
        $transactional = new FakeTransactional();

        $result = $this->handler(
            refreshTokenHasher: $refreshTokenHasher,
            refreshSessions: $refreshSessions,
            transactional: $transactional,
        )->__invoke(new LogoutCommand('   '));

        self::assertInstanceOf(LogoutResult::class, $result);
        self::assertSame([], $refreshTokenHasher->receivedRawRefreshTokens);
        self::assertSame([], $refreshSessions->lookedUpTokenHashes);
        self::assertSame([], $refreshSessions->savedSessions);
        self::assertSame(0, $transactional->transactionCallCount);
    }

    public function testUnknownTokenReturnsSuccess(): void
    {
        $refreshTokenHasher = new FakeRefreshTokenHasher(['unknown-raw-token' => 'unknown-token-hash']);
        $transactional = new FakeTransactional();
        $refreshSessions = new FakeRefreshSessionRepository([], $transactional);

        $result = $this->handler(
            refreshTokenHasher: $refreshTokenHasher,
            refreshSessions: $refreshSessions,
            transactional: $transactional,
        )->__invoke(new LogoutCommand('unknown-raw-token'));

        self::assertInstanceOf(LogoutResult::class, $result);
        self::assertSame(['unknown-raw-token'], $refreshTokenHasher->receivedRawRefreshTokens);
        self::assertSame(['unknown-token-hash'], $refreshSessions->lookedUpTokenHashes);
        self::assertSame([true], $refreshSessions->findByTokenHashForUpdateInsideTransaction);
        self::assertSame([], $refreshSessions->savedSessions);
        self::assertSame(1, $transactional->transactionCallCount);
    }

    public function testKnownTokenIsHashedFoundRevokedAndSaved(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $session = self::currentSession();
        $refreshSessions = new FakeRefreshSessionRepository([$session]);

        $result = $this->handler(
            refreshTokenHasher: new FakeRefreshTokenHasher(['current-raw-token' => 'current-token-hash']),
            refreshSessions: $refreshSessions,
            clock: new FakeClock($now),
        )->__invoke(new LogoutCommand('current-raw-token'));

        self::assertInstanceOf(LogoutResult::class, $result);
        self::assertTrue($session->isRevoked());
        self::assertFalse($session->isUsable($now));
        self::assertSame([$session], $refreshSessions->savedSessions);
    }

    public function testAlreadyRevokedTokenStillReturnsSuccess(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $session = self::currentSession();
        $session->revoke($now->modify('-1 hour'));
        $refreshSessions = new FakeRefreshSessionRepository([$session]);

        $result = $this->handler(
            refreshTokenHasher: new FakeRefreshTokenHasher(['current-raw-token' => 'current-token-hash']),
            refreshSessions: $refreshSessions,
            clock: new FakeClock($now),
        )->__invoke(new LogoutCommand('current-raw-token'));

        self::assertInstanceOf(LogoutResult::class, $result);
        self::assertTrue($session->isRevoked());
    }

    public function testLogoutRevokesOnlyCurrentSessionNotWholeFamily(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $currentSession = self::currentSession();
        $otherSession = self::otherSessionInSameFamily();
        $refreshSessions = new FakeRefreshSessionRepository([$currentSession, $otherSession]);

        $this->handler(
            refreshTokenHasher: new FakeRefreshTokenHasher(['current-raw-token' => 'current-token-hash']),
            refreshSessions: $refreshSessions,
            clock: new FakeClock($now),
        )->__invoke(new LogoutCommand('current-raw-token'));

        self::assertTrue($currentSession->isRevoked());
        self::assertTrue($otherSession->isUsable($now));
        self::assertFalse($otherSession->isRevoked());
        self::assertSame([], $refreshSessions->revokedFamilyIds);
    }

    public function testRawRefreshTokenIsNotPersisted(): void
    {
        $refreshSessions = new FakeRefreshSessionRepository([self::currentSession()]);

        $this->handler(
            refreshTokenHasher: new FakeRefreshTokenHasher(['current-raw-token' => 'current-token-hash']),
            refreshSessions: $refreshSessions,
        )->__invoke(new LogoutCommand('current-raw-token'));

        self::assertCount(1, $refreshSessions->savedSessions);
        self::assertSame('current-token-hash', $refreshSessions->savedSessions[0]->tokenHash()->value());
        self::assertNotSame('current-raw-token', $refreshSessions->savedSessions[0]->tokenHash()->value());
    }

    public function testRepositoryLookupAndSaveHappenInsideTransactionForKnownToken(): void
    {
        $transactional = new FakeTransactional();
        $refreshSessions = new FakeRefreshSessionRepository([self::currentSession()], $transactional);

        $this->handler(
            refreshTokenHasher: new FakeRefreshTokenHasher(['current-raw-token' => 'current-token-hash']),
            refreshSessions: $refreshSessions,
            transactional: $transactional,
        )->__invoke(new LogoutCommand('current-raw-token'));

        self::assertSame(1, $transactional->transactionCallCount);
        self::assertSame([true], $refreshSessions->findByTokenHashForUpdateInsideTransaction);
        self::assertSame([true], $refreshSessions->saveInsideTransaction);
    }

    public function testNoMessageBusUsage(): void
    {
        $constructor = (new ReflectionClass(LogoutHandler::class))->getConstructor();
        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            self::assertNotNull($type);
            self::assertStringNotContainsString('MessageBus' . 'Interface', (string) $type);
        }
    }

    public function testNoAccessTokenOrJwtUsage(): void
    {
        $constructor = (new ReflectionClass(LogoutHandler::class))->getConstructor();
        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $type = (string) $parameter->getType();
            self::assertStringNotContainsString('AccessToken', $type);
            self::assertStringNotContainsString('Jwt', $type);
            self::assertStringNotContainsString('JWT', $type);
        }
    }

    private function handler(
        ?FakeRefreshTokenHasher $refreshTokenHasher = null,
        ?FakeRefreshSessionRepository $refreshSessions = null,
        ?FakeTransactional $transactional = null,
        ?FakeClock $clock = null,
    ): LogoutHandler {
        return new LogoutHandler(
            new RefreshSessionRevoker(
                $refreshTokenHasher ?? new FakeRefreshTokenHasher(['current-raw-token' => 'current-token-hash']),
                $refreshSessions ?? new FakeRefreshSessionRepository([self::currentSession()]),
                $clock ?? new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
            ),
            $transactional ?? new FakeTransactional(),
        );
    }

    private static function currentSession(): RefreshSession
    {
        return RefreshSession::create(
            RefreshSessionId::fromString(self::CURRENT_SESSION_ID),
            AccountId::fromString(self::ACCOUNT_ID),
            RefreshTokenHash::fromString('current-token-hash'),
            RefreshSessionFamilyId::fromString(self::FAMILY_ID),
            new DateTimeImmutable('2026-06-03T09:10:11+00:00'),
            new DateTimeImmutable('2026-05-04T09:00:11+00:00'),
        );
    }

    private static function otherSessionInSameFamily(): RefreshSession
    {
        return RefreshSession::create(
            RefreshSessionId::fromString(self::OTHER_SESSION_ID),
            AccountId::fromString(self::ACCOUNT_ID),
            RefreshTokenHash::fromString('other-token-hash'),
            RefreshSessionFamilyId::fromString(self::FAMILY_ID),
            new DateTimeImmutable('2026-06-03T09:10:11+00:00'),
            new DateTimeImmutable('2026-05-04T09:00:11+00:00'),
        );
    }
}

final class FakeRefreshTokenHasher implements RefreshTokenHasherInterface
{
    /** @var list<string> */
    public array $receivedRawRefreshTokens = [];

    /** @param array<string, string> $hashesByRawRefreshToken */
    public function __construct(
        private readonly array $hashesByRawRefreshToken = [],
    ) {
    }

    public function hash(string $rawRefreshToken): RefreshTokenHash
    {
        $this->receivedRawRefreshTokens[] = $rawRefreshToken;

        return RefreshTokenHash::fromString($this->hashesByRawRefreshToken[$rawRefreshToken] ?? 'unknown-token-hash');
    }
}

final class FakeRefreshSessionRepository implements RefreshSessionRepositoryInterface
{
    /** @var list<string> */
    public array $lookedUpTokenHashes = [];

    /** @var list<RefreshSession> */
    public array $savedSessions = [];

    /** @var list<bool> */
    public array $saveInsideTransaction = [];

    /** @var list<bool> */
    public array $findByTokenHashForUpdateInsideTransaction = [];

    /** @var list<RefreshSessionFamilyId> */
    public array $revokedFamilyIds = [];

    /** @var array<string, RefreshSession> */
    private array $sessionsByTokenHash = [];

    /** @param list<RefreshSession> $sessions */
    public function __construct(array $sessions = [], private readonly ?FakeTransactional $transactional = null)
    {
        foreach ($sessions as $session) {
            $this->sessionsByTokenHash[$session->tokenHash()->value()] = $session;
        }
    }

    public function save(RefreshSession $session): void
    {
        $this->saveInsideTransaction[] = $this->transactional !== null && $this->transactional->isInsideTransaction;
        $this->savedSessions[] = $session;
        $this->sessionsByTokenHash[$session->tokenHash()->value()] = $session;
    }

    public function find(RefreshSessionId $id): ?RefreshSession
    {
        throw new LogicException('FakeRefreshSessionRepository::find() is not used by these tests.');
    }

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshSession
    {
        throw new LogicException('FakeRefreshSessionRepository::findByTokenHash() is not used by these tests.');
    }

    public function findByTokenHashForUpdate(RefreshTokenHash $tokenHash): ?RefreshSession
    {
        $this->lookedUpTokenHashes[] = $tokenHash->value();
        $this->findByTokenHashForUpdateInsideTransaction[] = $this->transactional !== null
            && $this->transactional->isInsideTransaction;

        return $this->sessionsByTokenHash[$tokenHash->value()] ?? null;
    }

    public function revokeFamily(RefreshSessionFamilyId $familyId, DateTimeImmutable $revokedAt): void
    {
        $this->revokedFamilyIds[] = $familyId;
    }
}

final class FakeClock implements ClockInterface
{
    public function __construct(
        private readonly DateTimeImmutable $now,
    ) {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class FakeTransactional implements TransactionalInterface
{
    public int $transactionCallCount = 0;
    public bool $isInsideTransaction = false;

    public function transactional(callable $operation): mixed
    {
        ++$this->transactionCallCount;
        $this->isInsideTransaction = true;

        try {
            return $operation();
        } finally {
            $this->isInsideTransaction = false;
        }
    }
}
