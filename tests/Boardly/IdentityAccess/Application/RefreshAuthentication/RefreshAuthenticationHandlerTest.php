<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Application\RefreshAuthentication;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Application\Port\AccessToken;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\RefreshAuthentication\InvalidRefreshToken;
use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationCommand;
use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationHandler;
use App\Boardly\IdentityAccess\Application\RefreshAuthentication\RefreshAuthenticationResult;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeImmutable;
use LogicException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RefreshAuthenticationHandlerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string OLD_SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e';
    private const string NEW_SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f';
    private const string FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c30';
    private const string REPLACEMENT_SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c31';
    private const string VALID_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testSuccessfulRefreshRotatesTokenAndIssuesNewTokens(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $accessTokenExpiresAt = new DateTimeImmutable('2026-05-04T09:25:11+00:00');
        $oldSession = self::usableSession();
        $refreshSessions = new FakeRefreshSessionRepository([$oldSession]);
        $accessTokenIssuer = new FakeAccessTokenIssuer('new-access-token', $accessTokenExpiresAt, 900);
        $refreshTokenGenerator = new FakeRefreshTokenGenerator('new-raw-refresh-token');
        $refreshTokenHasher = new FakeRefreshTokenHasher([
            'old-raw-refresh-token' => 'old-token-hash',
            'new-raw-refresh-token' => 'new-token-hash',
        ]);

        $result = $this->handler(
            refreshTokenHasher: $refreshTokenHasher,
            refreshTokenGenerator: $refreshTokenGenerator,
            refreshSessions: $refreshSessions,
            accounts: new FakeAccountRepository([self::activeAccount()]),
            accessTokenIssuer: $accessTokenIssuer,
            transactional: new FakeTransactional(),
            clock: new FakeClock($now),
            idGenerator: new FakeIdGenerator([self::NEW_SESSION_ID]),
        )->__invoke(new RefreshAuthenticationCommand('old-raw-refresh-token', 'Mozilla/5.0', '203.0.113.10'));

        self::assertInstanceOf(RefreshAuthenticationResult::class, $result);
        self::assertSame('new-access-token', $result->accessToken());
        self::assertSame($accessTokenExpiresAt, $result->accessTokenExpiresAt());
        self::assertSame(900, $result->accessTokenExpiresInSeconds());
        self::assertSame('new-raw-refresh-token', $result->rawRefreshToken());
        self::assertEquals($now->modify('+30 days'), $result->refreshTokenExpiresAt());
        self::assertSame(1, $accessTokenIssuer->issueCallCount);
        self::assertSame(1, $refreshTokenGenerator->generateCallCount);
        self::assertSame(['old-raw-refresh-token', 'new-raw-refresh-token'], $refreshTokenHasher->receivedRawRefreshTokens);
    }

    public function testSuccessfulRefreshReplacesOldSessionAndStoresNewSessionInSameFamily(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $transactional = new FakeTransactional();
        $oldSession = self::usableSession();
        $refreshSessions = new FakeRefreshSessionRepository([$oldSession], $transactional);

        $this->successfulHandler(
            refreshSessions: $refreshSessions,
            transactional: $transactional,
            clock: new FakeClock($now),
        )->__invoke(self::validCommand());

        self::assertSame(1, $transactional->transactionCallCount);
        self::assertSame([true], $refreshSessions->findByTokenHashForUpdateInsideTransaction);
        self::assertSame([true, true], $refreshSessions->saveInsideTransaction);
        self::assertCount(2, $refreshSessions->savedSessions);

        $savedOldSession = $refreshSessions->savedSessions[0];
        $newSession = $refreshSessions->savedSessions[1];

        self::assertSame(self::OLD_SESSION_ID, $savedOldSession->id()->value());
        self::assertFalse($savedOldSession->isUsable($now));
        self::assertTrue($savedOldSession->isRevoked());
        self::assertTrue($savedOldSession->wasReplaced());
        self::assertSame(self::NEW_SESSION_ID, $savedOldSession->replacedByTokenId()?->value());
        self::assertSame($now, $savedOldSession->lastUsedAt());

        self::assertSame(self::NEW_SESSION_ID, $newSession->id()->value());
        self::assertSame(self::ACCOUNT_ID, $newSession->accountId()->value());
        self::assertSame(self::FAMILY_ID, $newSession->familyId()->value());
        self::assertSame('new-token-hash', $newSession->tokenHash()->value());
        self::assertSame($now, $newSession->createdAt());
        self::assertEquals($now->modify('+30 days'), $newSession->expiresAt());
        self::assertTrue($newSession->isUsable($now));
    }

    public function testRawOldAndNewRefreshTokensAreNotPersisted(): void
    {
        $refreshSessions = new FakeRefreshSessionRepository([self::usableSession()]);

        $result = $this->successfulHandler(refreshSessions: $refreshSessions)->__invoke(self::validCommand());

        self::assertSame('new-raw-refresh-token', $result->rawRefreshToken());

        foreach ($refreshSessions->savedSessions as $session) {
            self::assertNotSame('old-raw-refresh-token', $session->tokenHash()->value());
            self::assertNotSame('new-raw-refresh-token', $session->tokenHash()->value());
        }
    }

    public function testAccessTokenIssuingFailureDoesNotPersistRefreshSessionRotation(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $transactional = new FakeTransactional();
        $oldSession = self::usableSession();
        $refreshSessions = new FakeRefreshSessionRepository([$oldSession], $transactional);
        $accessTokenIssuer = new FakeAccessTokenIssuer(exception: new RuntimeException('Access token issuing failed.'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Access token issuing failed.');

        try {
            $this->successfulHandler(
                refreshSessions: $refreshSessions,
                accessTokenIssuer: $accessTokenIssuer,
                transactional: $transactional,
                clock: new FakeClock($now),
            )->__invoke(self::validCommand());
        } finally {
            self::assertSame(1, $accessTokenIssuer->issueCallCount);
            self::assertSame(1, $transactional->transactionCallCount);
            self::assertSame([true], $refreshSessions->findByTokenHashForUpdateInsideTransaction);
            self::assertSame([], $refreshSessions->savedSessions);
            self::assertTrue($oldSession->isUsable($now));
            self::assertFalse($oldSession->isRevoked());
            self::assertFalse($oldSession->wasReplaced());
            self::assertNull($oldSession->replacedByTokenId());
            self::assertNull($oldSession->lastUsedAt());
        }
    }

    public function testEmptyTokenFailsWithoutSideEffects(): void
    {
        $this->assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(new RefreshAuthenticationCommand('   ', null, null));
    }

    public function testUnknownTokenFailsWithoutSideEffects(): void
    {
        $transactional = new FakeTransactional();
        $refreshSessions = new FakeRefreshSessionRepository([], $transactional);

        $this->assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(
            self::validCommand(),
            $refreshSessions,
            transactional: $transactional,
        );

        self::assertSame(1, $transactional->transactionCallCount);
        self::assertSame([true], $refreshSessions->findByTokenHashForUpdateInsideTransaction);
    }

    public function testExpiredTokenFailsWithoutSideEffects(): void
    {
        $this->assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(
            self::validCommand(),
            new FakeRefreshSessionRepository([
                self::usableSession(expiresAt: new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
            ]),
        );
    }

    public function testRevokedTokenFailsWithoutSideEffects(): void
    {
        $session = self::usableSession();
        $session->revoke(new DateTimeImmutable('2026-05-04T09:09:11+00:00'));

        $this->assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(
            self::validCommand(),
            new FakeRefreshSessionRepository([$session]),
        );
    }

    public function testReplacedTokenReuseFailsAndRevokesFamilyInTransaction(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $session = self::usableSession();
        $session->replaceWith(RefreshSessionId::fromString(self::REPLACEMENT_SESSION_ID), $now->modify('-1 minute'));
        $transactional = new FakeTransactional();
        $refreshSessions = new FakeRefreshSessionRepository([$session], $transactional);
        $accessTokenIssuer = new FakeAccessTokenIssuer();
        $refreshTokenGenerator = new FakeRefreshTokenGenerator();

        $this->expectException(InvalidRefreshToken::class);

        try {
            $this->successfulHandler(
                accessTokenIssuer: $accessTokenIssuer,
                refreshTokenGenerator: $refreshTokenGenerator,
                refreshSessions: $refreshSessions,
                transactional: $transactional,
                clock: new FakeClock($now),
            )->__invoke(self::validCommand());
        } finally {
            self::assertSame(0, $accessTokenIssuer->issueCallCount);
            self::assertSame(0, $refreshTokenGenerator->generateCallCount);
            self::assertSame([], $refreshSessions->savedSessions);
            self::assertSame(1, $transactional->transactionCallCount);
            self::assertSame([true], $refreshSessions->revokeFamilyInsideTransaction);
            self::assertSame([self::FAMILY_ID], array_map(
                static fn (RefreshSessionFamilyId $familyId): string => $familyId->value(),
                $refreshSessions->revokedFamilyIds,
            ));
            self::assertSame([$now], $refreshSessions->revokedFamilyAt);
        }
    }

    public function testMissingAccountFailsWithoutSideEffects(): void
    {
        $this->assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(
            self::validCommand(),
            new FakeRefreshSessionRepository([self::usableSession()]),
            new FakeAccountRepository(),
        );
    }

    public function testNonActiveAccountFailsWithoutSideEffects(): void
    {
        $this->assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(
            self::validCommand(),
            new FakeRefreshSessionRepository([self::usableSession()]),
            new FakeAccountRepository([self::accountWithStatus(AccountStatus::pendingApproval())]),
        );
    }

    public function testHandlerDoesNotDependOnMessageBus(): void
    {
        $constructor = (new ReflectionClass(RefreshAuthenticationHandler::class))->getConstructor();
        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            self::assertNotNull($type);
            self::assertStringNotContainsString('MessageBus' . 'Interface', (string) $type);
        }
    }

    private function assertInvalidRefreshTokenHasNoTokenOrSessionSideEffects(
        RefreshAuthenticationCommand $command,
        ?FakeRefreshSessionRepository $refreshSessions = null,
        ?FakeAccountRepository $accounts = null,
        ?FakeTransactional $transactional = null,
    ): void {
        $accessTokenIssuer = new FakeAccessTokenIssuer();
        $refreshTokenGenerator = new FakeRefreshTokenGenerator();
        $refreshSessions ??= new FakeRefreshSessionRepository([self::usableSession()]);

        $this->expectException(InvalidRefreshToken::class);

        try {
            $this->successfulHandler(
                accessTokenIssuer: $accessTokenIssuer,
                refreshTokenGenerator: $refreshTokenGenerator,
                refreshSessions: $refreshSessions,
                accounts: $accounts ?? new FakeAccountRepository([self::activeAccount()]),
                transactional: $transactional,
            )->__invoke($command);
        } finally {
            self::assertSame(0, $accessTokenIssuer->issueCallCount);
            self::assertSame(0, $refreshTokenGenerator->generateCallCount);
            self::assertSame([], $refreshSessions->savedSessions);
        }
    }

    private function successfulHandler(
        ?FakeRefreshTokenHasher $refreshTokenHasher = null,
        ?FakeRefreshTokenGenerator $refreshTokenGenerator = null,
        ?FakeRefreshSessionRepository $refreshSessions = null,
        ?FakeAccountRepository $accounts = null,
        ?FakeAccessTokenIssuer $accessTokenIssuer = null,
        ?FakeTransactional $transactional = null,
        ?FakeClock $clock = null,
        ?FakeIdGenerator $idGenerator = null,
    ): RefreshAuthenticationHandler {
        return $this->handler(
            $refreshTokenHasher ?? new FakeRefreshTokenHasher([
                'old-raw-refresh-token' => 'old-token-hash',
                'new-raw-refresh-token' => 'new-token-hash',
            ]),
            $refreshTokenGenerator ?? new FakeRefreshTokenGenerator('new-raw-refresh-token'),
            $refreshSessions ?? new FakeRefreshSessionRepository([self::usableSession()]),
            $accounts ?? new FakeAccountRepository([self::activeAccount()]),
            $accessTokenIssuer ?? new FakeAccessTokenIssuer(),
            $transactional ?? new FakeTransactional(),
            $clock ?? new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
            $idGenerator ?? new FakeIdGenerator([self::NEW_SESSION_ID]),
        );
    }

    private function handler(
        FakeRefreshTokenHasher $refreshTokenHasher,
        FakeRefreshTokenGenerator $refreshTokenGenerator,
        FakeRefreshSessionRepository $refreshSessions,
        FakeAccountRepository $accounts,
        FakeAccessTokenIssuer $accessTokenIssuer,
        FakeTransactional $transactional,
        FakeClock $clock,
        FakeIdGenerator $idGenerator,
    ): RefreshAuthenticationHandler {
        return new RefreshAuthenticationHandler(
            $refreshTokenHasher,
            $refreshTokenGenerator,
            $refreshSessions,
            $accounts,
            $accessTokenIssuer,
            $transactional,
            $clock,
            $idGenerator,
        );
    }

    private static function validCommand(): RefreshAuthenticationCommand
    {
        return new RefreshAuthenticationCommand('old-raw-refresh-token', null, null);
    }

    private static function activeAccount(): Account
    {
        return self::accountWithStatus(AccountStatus::active());
    }

    private static function accountWithStatus(AccountStatus $status): Account
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');

        return Account::reconstitute(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('refresh.account@example.com'),
            PasswordHash::fromString(self::VALID_HASH),
            AccountName::fromString('Refresh Account'),
            $status,
            false,
            $now,
            $now,
            ($status->isActive() || $status->isDisabled()) ? $now : null,
            $status->isRejected() ? $now : null,
            $status->isDisabled() ? $now : null,
        );
    }

    private static function usableSession(?DateTimeImmutable $expiresAt = null): RefreshSession
    {
        return RefreshSession::create(
            RefreshSessionId::fromString(self::OLD_SESSION_ID),
            AccountId::fromString(self::ACCOUNT_ID),
            RefreshTokenHash::fromString('old-token-hash'),
            RefreshSessionFamilyId::fromString(self::FAMILY_ID),
            $expiresAt ?? new DateTimeImmutable('2026-06-03T09:10:11+00:00'),
            new DateTimeImmutable('2026-05-04T09:00:11+00:00'),
        );
    }
}

final class FakeAccountRepository implements AccountRepositoryInterface
{
    /** @var array<string, Account> */
    private array $accountsById = [];

    /** @param list<Account> $accounts */
    public function __construct(array $accounts = [])
    {
        foreach ($accounts as $account) {
            $this->accountsById[$account->id()->value()] = $account;
        }
    }

    public function save(Account $account): void
    {
        throw new LogicException('FakeAccountRepository::save() is not used by these tests.');
    }

    public function get(AccountId $id): Account
    {
        throw AccountNotFound::withId($id);
    }

    public function find(AccountId $id): ?Account
    {
        return $this->accountsById[$id->value()] ?? null;
    }

    public function findByEmail(Email $email): ?Account
    {
        throw new LogicException('FakeAccountRepository::findByEmail() is not used by these tests.');
    }

    public function existsByEmail(Email $email): bool
    {
        throw new LogicException('FakeAccountRepository::existsByEmail() is not used by these tests.');
    }

    public function countActiveSystemAdmins(): int
    {
        throw new LogicException('FakeAccountRepository::countActiveSystemAdmins() is not used by these tests.');
    }
}

final class FakeAccessTokenIssuer implements AccessTokenIssuerInterface
{
    public int $issueCallCount = 0;

    public function __construct(
        private readonly string $token = 'issued-access-token',
        private readonly ?DateTimeImmutable $expiresAt = null,
        private readonly int $expiresInSeconds = 900,
        private readonly ?RuntimeException $exception = null,
    ) {
    }

    public function issueForAccount(AccountId $accountId, DateTimeImmutable $issuedAt, ?int $ttlSeconds = null): AccessToken
    {
        ++$this->issueCallCount;

        if (null !== $this->exception) {
            throw $this->exception;
        }

        return new AccessToken(
            $this->token,
            $this->expiresAt ?? $issuedAt->modify(sprintf('+%d seconds', $this->expiresInSeconds)),
            $this->expiresInSeconds,
        );
    }
}

final class FakeRefreshTokenGenerator implements RefreshTokenGeneratorInterface
{
    public int $generateCallCount = 0;

    public function __construct(
        private readonly string $rawRefreshToken = 'new-raw-refresh-token',
    ) {
    }

    public function generate(): string
    {
        ++$this->generateCallCount;

        return $this->rawRefreshToken;
    }
}

final class FakeRefreshTokenHasher implements RefreshTokenHasherInterface
{
    /** @var list<string> */
    public array $receivedRawRefreshTokens = [];

    /** @param array<string, string> $hashesByRawRefreshToken */
    public function __construct(
        private readonly array $hashesByRawRefreshToken,
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
    /** @var list<RefreshSession> */
    public array $savedSessions = [];

    /** @var list<bool> */
    public array $saveInsideTransaction = [];

    /** @var list<bool> */
    public array $findByTokenHashForUpdateInsideTransaction = [];

    /** @var list<RefreshSessionFamilyId> */
    public array $revokedFamilyIds = [];

    /** @var list<DateTimeImmutable> */
    public array $revokedFamilyAt = [];

    /** @var list<bool> */
    public array $revokeFamilyInsideTransaction = [];

    /** @var array<string, RefreshSession> */
    private array $sessionsByTokenHash = [];

    /**
     * @param list<RefreshSession> $sessions
     */
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
        return $this->sessionsByTokenHash[$tokenHash->value()] ?? null;
    }

    public function findByTokenHashForUpdate(RefreshTokenHash $tokenHash): ?RefreshSession
    {
        $this->findByTokenHashForUpdateInsideTransaction[] = $this->transactional !== null
            && $this->transactional->isInsideTransaction;

        return $this->sessionsByTokenHash[$tokenHash->value()] ?? null;
    }

    public function revokeFamily(RefreshSessionFamilyId $familyId, DateTimeImmutable $revokedAt): void
    {
        $this->revokeFamilyInsideTransaction[] = $this->transactional !== null && $this->transactional->isInsideTransaction;
        $this->revokedFamilyIds[] = $familyId;
        $this->revokedFamilyAt[] = $revokedAt;
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

final class FakeIdGenerator implements IdGeneratorInterface
{
    public int $generateCallCount = 0;

    /** @param list<string> $ids */
    public function __construct(
        private readonly array $ids,
    ) {
    }

    public function generate(): string
    {
        if (!isset($this->ids[$this->generateCallCount])) {
            throw new LogicException('No fake id configured for this generate() call.');
        }

        return $this->ids[$this->generateCallCount++];
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
