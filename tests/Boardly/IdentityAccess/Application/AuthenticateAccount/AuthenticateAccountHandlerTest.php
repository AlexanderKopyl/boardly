<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Application\AuthenticateAccount;

use App\Boardly\IdentityAccess\Application\AuthenticateAccount\AuthenticateAccountCommand;
use App\Boardly\IdentityAccess\Application\AuthenticateAccount\AuthenticateAccountHandler;
use App\Boardly\IdentityAccess\Application\AuthenticateAccount\AuthenticateAccountResult;
use App\Boardly\IdentityAccess\Application\AuthenticateAccount\AuthenticatedAccount;
use App\Boardly\IdentityAccess\Application\Exception\AccountNotActive;
use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Application\Exception\InvalidCredentials;
use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Security\AccessToken;
use App\Boardly\IdentityAccess\Application\Port\PasswordVerifierInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;
use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
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
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class AuthenticateAccountHandlerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string SESSION_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e';
    private const string FAMILY_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f';
    private const string VALID_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testSuccessfulActiveAccountAuthenticationReturnsAccessTokenAndAccountDto(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $accessTokenExpiresAt = new DateTimeImmutable('2026-05-04T09:25:11+00:00');
        $repository = new FakeAccountRepository([self::activeAccount()]);
        $refreshSessions = new FakeRefreshSessionRepository();

        $result = $this->handler(
            $repository,
            new FakePasswordVerifier(true),
            new FakeAccessTokenIssuer('issued-access-token', $accessTokenExpiresAt, 900),
            new FakeRefreshTokenGenerator('raw-refresh-token'),
            new FakeRefreshTokenHasher('hashed-refresh-token'),
            $refreshSessions,
            new FakeTransactional(),
            new FakeClock($now),
            new FakeIdGenerator([self::SESSION_ID, self::FAMILY_ID]),
        )->__invoke(new AuthenticateAccountCommand(
            '  Login.Account@Example.COM ',
            'plain-password',
            'Mozilla/5.0',
            '203.0.113.10',
        ));

        self::assertInstanceOf(AuthenticateAccountResult::class, $result);
        self::assertSame('issued-access-token', $result->accessToken());
        self::assertSame($accessTokenExpiresAt, $result->accessTokenExpiresAt());
        self::assertSame(900, $result->accessTokenExpiresInSeconds());
        self::assertSame('raw-refresh-token', $result->rawRefreshToken());
        self::assertEquals($now->modify('+30 days'), $result->refreshTokenExpiresAt());

        self::assertInstanceOf(AuthenticatedAccount::class, $result->account());
        self::assertSame(self::ACCOUNT_ID, $result->account()->id());
        self::assertSame('login.account@example.com', $result->account()->email());
        self::assertSame('Login Account', $result->account()->name());
        self::assertSame('active', $result->account()->status());
        self::assertCount(1, $refreshSessions->savedSessions);
    }

    public function testSuccessfulAuthenticationGeneratesAndHashesRefreshTokenBeforePersistence(): void
    {
        $refreshTokenGenerator = new FakeRefreshTokenGenerator('raw-refresh-token');
        $refreshTokenHasher = new FakeRefreshTokenHasher('hashed-refresh-token');
        $refreshSessions = new FakeRefreshSessionRepository();

        $result = $this->successfulHandler(
            refreshTokenGenerator: $refreshTokenGenerator,
            refreshTokenHasher: $refreshTokenHasher,
            refreshSessions: $refreshSessions,
        )->__invoke(self::validCommand());

        self::assertSame('raw-refresh-token', $result->rawRefreshToken());
        self::assertSame(1, $refreshTokenGenerator->generateCallCount);
        self::assertSame(['raw-refresh-token'], $refreshTokenHasher->receivedRawRefreshTokens);
        self::assertSame('hashed-refresh-token', $refreshSessions->savedSessions[0]->tokenHash()->value());
        self::assertNotSame('raw-refresh-token', $refreshSessions->savedSessions[0]->tokenHash()->value());
    }

    public function testRefreshSessionIsPersistedWithMetadataInsideTransaction(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $transactional = new FakeTransactional();
        $refreshSessions = new FakeRefreshSessionRepository($transactional);

        $this->successfulHandler(
            refreshSessions: $refreshSessions,
            transactional: $transactional,
            clock: new FakeClock($now),
        )->__invoke(self::validCommand());

        self::assertSame(1, $transactional->transactionCallCount);
        self::assertSame([true], $refreshSessions->saveInsideTransaction);
        self::assertCount(1, $refreshSessions->savedSessions);

        $session = $refreshSessions->savedSessions[0];
        self::assertSame(self::SESSION_ID, $session->id()->value());
        self::assertSame(self::ACCOUNT_ID, $session->accountId()->value());
        self::assertSame('hashed-refresh-token', $session->tokenHash()->value());
        self::assertSame(self::FAMILY_ID, $session->familyId()->value());
        self::assertSame($now, $session->createdAt());
        self::assertEquals($now->modify('+30 days'), $session->expiresAt());
        self::assertNull($session->userAgentHash());
        self::assertNull($session->ipHash());
    }

    public function testAccessTokenIssuerIsCalledWithAccountIdAndCurrentTime(): void
    {
        $now = new DateTimeImmutable('2026-05-04T09:10:11+00:00');
        $accessTokenIssuer = new FakeAccessTokenIssuer();

        $this->successfulHandler(
            accessTokenIssuer: $accessTokenIssuer,
            clock: new FakeClock($now),
        )->__invoke(self::validCommand());

        self::assertSame([self::ACCOUNT_ID], array_map(
            static fn (AccountId $accountId): string => $accountId->value(),
            $accessTokenIssuer->receivedAccountIds,
        ));
        self::assertSame([$now], $accessTokenIssuer->receivedIssuedAt);
    }

    public function testUnknownEmailThrowsInvalidCredentialsWithoutLoginSideEffects(): void
    {
        $accessTokenIssuer = new FakeAccessTokenIssuer();
        $refreshTokenGenerator = new FakeRefreshTokenGenerator();
        $refreshSessions = new FakeRefreshSessionRepository();

        $this->expectException(InvalidCredentials::class);

        try {
            $this->handler(
                new FakeAccountRepository(),
                new FakePasswordVerifier(true),
                $accessTokenIssuer,
                $refreshTokenGenerator,
                new FakeRefreshTokenHasher(),
                $refreshSessions,
                new FakeTransactional(),
                new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
                new FakeIdGenerator([self::SESSION_ID, self::FAMILY_ID]),
            )->__invoke(self::validCommand());
        } finally {
            self::assertSame(0, $accessTokenIssuer->issueCallCount);
            self::assertSame(0, $refreshTokenGenerator->generateCallCount);
            self::assertSame([], $refreshSessions->savedSessions);
        }
    }

    public function testInvalidEmailFormatThrowsInvalidCredentialsWithoutLoginSideEffects(): void
    {
        $accessTokenIssuer = new FakeAccessTokenIssuer();
        $refreshTokenGenerator = new FakeRefreshTokenGenerator();
        $refreshSessions = new FakeRefreshSessionRepository();

        $this->expectException(InvalidCredentials::class);

        try {
            $this->handler(
                new FakeAccountRepository([self::activeAccount()]),
                new FakePasswordVerifier(true),
                $accessTokenIssuer,
                $refreshTokenGenerator,
                new FakeRefreshTokenHasher(),
                $refreshSessions,
                new FakeTransactional(),
                new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
                new FakeIdGenerator([self::SESSION_ID, self::FAMILY_ID]),
            )->__invoke(new AuthenticateAccountCommand('not-an-email', 'plain-password', null, null));
        } finally {
            self::assertSame(0, $accessTokenIssuer->issueCallCount);
            self::assertSame(0, $refreshTokenGenerator->generateCallCount);
            self::assertSame([], $refreshSessions->savedSessions);
        }
    }

    public function testWrongPasswordThrowsInvalidCredentialsWithoutLoginSideEffects(): void
    {
        $accessTokenIssuer = new FakeAccessTokenIssuer();
        $refreshTokenGenerator = new FakeRefreshTokenGenerator();
        $refreshSessions = new FakeRefreshSessionRepository();

        $this->expectException(InvalidCredentials::class);

        try {
            $this->handler(
                new FakeAccountRepository([self::activeAccount()]),
                new FakePasswordVerifier(false),
                $accessTokenIssuer,
                $refreshTokenGenerator,
                new FakeRefreshTokenHasher(),
                $refreshSessions,
                new FakeTransactional(),
                new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
                new FakeIdGenerator([self::SESSION_ID, self::FAMILY_ID]),
            )->__invoke(self::validCommand());
        } finally {
            self::assertSame(0, $accessTokenIssuer->issueCallCount);
            self::assertSame(0, $refreshTokenGenerator->generateCallCount);
            self::assertSame([], $refreshSessions->savedSessions);
        }
    }

    public function testPendingApprovalAccountThrowsAccountNotActive(): void
    {
        $this->assertNonActiveAccountThrowsAccountNotActive(self::accountWithStatus(AccountStatus::pendingApproval()));
    }

    public function testRejectedAccountThrowsAccountNotActive(): void
    {
        $this->assertNonActiveAccountThrowsAccountNotActive(self::accountWithStatus(AccountStatus::rejected()));
    }

    public function testDisabledAccountThrowsAccountNotActive(): void
    {
        $this->assertNonActiveAccountThrowsAccountNotActive(self::accountWithStatus(AccountStatus::disabled()));
    }

    public function testResultDoesNotExposePasswordHashOrRefreshTokenHashOrDomainSession(): void
    {
        $resultReflection = new ReflectionClass(AuthenticateAccountResult::class);
        $accountReflection = new ReflectionClass(AuthenticatedAccount::class);

        $constructor = $resultReflection->getConstructor();
        self::assertNotNull($constructor);
        self::assertSame(
            [
                'accessToken',
                'accessTokenExpiresAt',
                'accessTokenExpiresInSeconds',
                'rawRefreshToken',
                'refreshTokenExpiresAt',
                'account',
            ],
            array_map(
                static fn (ReflectionParameter $parameter): string => $parameter->getName(),
                $constructor->getParameters(),
            ),
        );

        $resultMethods = self::publicMethodNames($resultReflection);
        $accountMethods = self::publicMethodNames($accountReflection);

        foreach (['password', 'passwordHash', 'tokenHash', 'refreshTokenHash', 'refreshSession', 'session'] as $unsafeMethod) {
            self::assertNotContains($unsafeMethod, $resultMethods);
            self::assertNotContains($unsafeMethod, $accountMethods);
        }
    }

    public function testHandlerDoesNotDependOnMessageBus(): void
    {
        $constructor = (new ReflectionClass(AuthenticateAccountHandler::class))->getConstructor();
        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            self::assertNotNull($type);
            self::assertStringNotContainsString('MessageBus' . 'Interface', (string) $type);
        }
    }

    private function assertNonActiveAccountThrowsAccountNotActive(Account $account): void
    {
        $accessTokenIssuer = new FakeAccessTokenIssuer();
        $refreshTokenGenerator = new FakeRefreshTokenGenerator();
        $refreshSessions = new FakeRefreshSessionRepository();

        $this->expectException(AccountNotActive::class);

        try {
            $this->handler(
                new FakeAccountRepository([$account]),
                new FakePasswordVerifier(true),
                $accessTokenIssuer,
                $refreshTokenGenerator,
                new FakeRefreshTokenHasher(),
                $refreshSessions,
                new FakeTransactional(),
                new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
                new FakeIdGenerator([self::SESSION_ID, self::FAMILY_ID]),
            )->__invoke(self::validCommand());
        } finally {
            self::assertSame(0, $accessTokenIssuer->issueCallCount);
            self::assertSame(0, $refreshTokenGenerator->generateCallCount);
            self::assertSame([], $refreshSessions->savedSessions);
        }
    }

    private function successfulHandler(
        ?FakeAccountRepository $repository = null,
        ?FakePasswordVerifier $passwordVerifier = null,
        ?FakeAccessTokenIssuer $accessTokenIssuer = null,
        ?FakeRefreshTokenGenerator $refreshTokenGenerator = null,
        ?FakeRefreshTokenHasher $refreshTokenHasher = null,
        ?FakeRefreshSessionRepository $refreshSessions = null,
        ?FakeTransactional $transactional = null,
        ?FakeClock $clock = null,
        ?FakeIdGenerator $idGenerator = null,
    ): AuthenticateAccountHandler {
        return $this->handler(
            $repository ?? new FakeAccountRepository([self::activeAccount()]),
            $passwordVerifier ?? new FakePasswordVerifier(true),
            $accessTokenIssuer ?? new FakeAccessTokenIssuer(),
            $refreshTokenGenerator ?? new FakeRefreshTokenGenerator(),
            $refreshTokenHasher ?? new FakeRefreshTokenHasher(),
            $refreshSessions ?? new FakeRefreshSessionRepository(),
            $transactional ?? new FakeTransactional(),
            $clock ?? new FakeClock(new DateTimeImmutable('2026-05-04T09:10:11+00:00')),
            $idGenerator ?? new FakeIdGenerator([self::SESSION_ID, self::FAMILY_ID]),
        );
    }

    private function handler(
        FakeAccountRepository $repository,
        FakePasswordVerifier $passwordVerifier,
        FakeAccessTokenIssuer $accessTokenIssuer,
        FakeRefreshTokenGenerator $refreshTokenGenerator,
        FakeRefreshTokenHasher $refreshTokenHasher,
        FakeRefreshSessionRepository $refreshSessions,
        FakeTransactional $transactional,
        FakeClock $clock,
        FakeIdGenerator $idGenerator,
    ): AuthenticateAccountHandler {
        return new AuthenticateAccountHandler(
            $repository,
            $passwordVerifier,
            $accessTokenIssuer,
            $refreshTokenGenerator,
            $refreshTokenHasher,
            $refreshSessions,
            $transactional,
            $clock,
            $idGenerator,
        );
    }

    private static function validCommand(): AuthenticateAccountCommand
    {
        return new AuthenticateAccountCommand('login.account@example.com', 'plain-password', null, null);
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
            Email::fromString('login.account@example.com'),
            PasswordHash::fromString(self::VALID_HASH),
            AccountName::fromString('Login Account'),
            $status,
            false,
            $now,
            $now,
            ($status->isActive() || $status->isDisabled()) ? $now : null,
            $status->isRejected() ? $now : null,
            $status->isDisabled() ? $now : null,
        );
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private static function publicMethodNames(ReflectionClass $reflection): array
    {
        $methodNames = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        sort($methodNames);

        return $methodNames;
    }
}

final class FakeAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var array<string, Account>
     */
    private array $accountsByEmail = [];

    /**
     * @param list<Account> $accounts
     */
    public function __construct(array $accounts = [])
    {
        foreach ($accounts as $account) {
            $this->accountsByEmail[$account->email()->value()] = $account;
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
        throw new LogicException('FakeAccountRepository::find() is not used by these tests.');
    }

    public function findByEmail(Email $email): ?Account
    {
        return $this->accountsByEmail[$email->value()] ?? null;
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

final class FakePasswordVerifier implements PasswordVerifierInterface
{
    /**
     * @var list<string>
     */
    public array $receivedPlainPasswords = [];

    /**
     * @var list<string>
     */
    public array $receivedPasswordHashes = [];

    public function __construct(
        private readonly bool $verified,
    ) {
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        $this->receivedPlainPasswords[] = $plainPassword;
        $this->receivedPasswordHashes[] = $passwordHash;

        return $this->verified;
    }
}

final class FakeAccessTokenIssuer implements AccessTokenIssuerInterface
{
    public int $issueCallCount = 0;

    /**
     * @var list<AccountId>
     */
    public array $receivedAccountIds = [];

    /**
     * @var list<DateTimeImmutable>
     */
    public array $receivedIssuedAt = [];

    public function __construct(
        private readonly string $token = 'issued-access-token',
        private readonly ?DateTimeImmutable $expiresAt = null,
        private readonly int $expiresInSeconds = 900,
    ) {
    }

    public function issueForAccount(AccountId $accountId, DateTimeImmutable $issuedAt, ?int $ttlSeconds = null): AccessToken
    {
        ++$this->issueCallCount;
        $this->receivedAccountIds[] = $accountId;
        $this->receivedIssuedAt[] = $issuedAt;

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
        private readonly string $rawRefreshToken = 'raw-refresh-token',
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
    /**
     * @var list<string>
     */
    public array $receivedRawRefreshTokens = [];

    public function __construct(
        private readonly string $hash = 'hashed-refresh-token',
    ) {
    }

    public function hash(string $rawRefreshToken): RefreshTokenHash
    {
        $this->receivedRawRefreshTokens[] = $rawRefreshToken;

        return RefreshTokenHash::fromString($this->hash);
    }
}

final class FakeRefreshSessionRepository implements RefreshSessionRepositoryInterface
{
    /**
     * @var list<RefreshSession>
     */
    public array $savedSessions = [];

    /**
     * @var list<bool>
     */
    public array $saveInsideTransaction = [];

    public function __construct(
        private readonly ?FakeTransactional $transactional = null,
    ) {
    }

    public function save(RefreshSession $session): void
    {
        $this->saveInsideTransaction[] = $this->transactional !== null && $this->transactional->isInsideTransaction;
        $this->savedSessions[] = $session;
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
        throw new LogicException('FakeRefreshSessionRepository::findByTokenHashForUpdate() is not used by these tests.');
    }

    public function revokeFamily(RefreshSessionFamilyId $familyId, DateTimeImmutable $revokedAt): void
    {
        throw new LogicException('FakeRefreshSessionRepository::revokeFamily() is not used by these tests.');
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

    /**
     * @param list<string> $ids
     */
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
