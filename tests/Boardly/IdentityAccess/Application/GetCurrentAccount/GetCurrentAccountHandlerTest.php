<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Application\GetCurrentAccount;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotActive;
use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountHandler;
use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountQuery;
use App\Boardly\IdentityAccess\Application\GetCurrentAccount\GetCurrentAccountResult;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class GetCurrentAccountHandlerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string VALID_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testActiveAccountReturnsSafeIdentityResult(): void
    {
        $repository = new FakeAccountRepository([self::activeAccount()]);

        $result = $this->handler($repository)->__invoke(
            new GetCurrentAccountQuery(AccountId::fromString(self::ACCOUNT_ID)),
        );

        self::assertInstanceOf(GetCurrentAccountResult::class, $result);
        self::assertSame(self::ACCOUNT_ID, $result->id());
        self::assertSame('current.account@example.com', $result->email());
        self::assertSame('Current Account', $result->name());
        self::assertSame('active', $result->status());
        self::assertSame([self::ACCOUNT_ID], $repository->findAccountIds);
    }

    public function testMissingAccountUsesStandardizedAuthFailure(): void
    {
        $repository = new FakeAccountRepository();

        $this->expectException(AccountNotActive::class);
        $this->expectExceptionMessage('Account is not active.');

        try {
            $this->handler($repository)->__invoke(
                new GetCurrentAccountQuery(AccountId::fromString(self::ACCOUNT_ID)),
            );
        } finally {
            self::assertSame([self::ACCOUNT_ID], $repository->findAccountIds);
        }
    }

    public function testInactiveAccountUsesStandardizedAuthFailure(): void
    {
        $repository = new FakeAccountRepository([self::inactiveAccount()]);

        $this->expectException(AccountNotActive::class);
        $this->expectExceptionMessage('Account is not active.');

        try {
            $this->handler($repository)->__invoke(
                new GetCurrentAccountQuery(AccountId::fromString(self::ACCOUNT_ID)),
            );
        } finally {
            self::assertSame([self::ACCOUNT_ID], $repository->findAccountIds);
        }
    }

    public function testResultExposesOnlySafeOutput(): void
    {
        $reflection = new ReflectionClass(GetCurrentAccountResult::class);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);
        self::assertSame(['id', 'email', 'name', 'status'], array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $constructor->getParameters(),
        ));

        $publicMethodNames = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        sort($publicMethodNames);
        self::assertSame(['__construct', 'email', 'id', 'name', 'status'], $publicMethodNames);

        foreach (
            [
                'account',
                'password',
                'passwordHash',
                'plainPassword',
                'refreshToken',
                'accessToken',
                'entity',
                'token',
                'isSystemAdmin',
            ] as $unsafeMethod
        ) {
            self::assertNotContains($unsafeMethod, $publicMethodNames, sprintf(
                'GetCurrentAccountResult must not expose %s().',
                $unsafeMethod,
            ));
        }
    }

    private function handler(FakeAccountRepository $repository): GetCurrentAccountHandler
    {
        return new GetCurrentAccountHandler($repository);
    }

    private static function activeAccount(): Account
    {
        return Account::reconstitute(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('current.account@example.com'),
            PasswordHash::fromString(self::VALID_HASH),
            AccountName::fromString('Current Account'),
            AccountStatus::active(),
            false,
            new DateTimeImmutable('2026-05-14T09:10:00+00:00'),
            new DateTimeImmutable('2026-05-14T09:10:00+00:00'),
            new DateTimeImmutable('2026-05-14T09:10:00+00:00'),
            null,
            null,
        );
    }

    private static function inactiveAccount(): Account
    {
        return Account::reconstitute(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString('current.account@example.com'),
            PasswordHash::fromString(self::VALID_HASH),
            AccountName::fromString('Current Account'),
            AccountStatus::pendingApproval(),
            false,
            new DateTimeImmutable('2026-05-14T09:10:00+00:00'),
            new DateTimeImmutable('2026-05-14T09:10:00+00:00'),
            null,
            null,
            null,
        );
    }
}

final class FakeAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var list<Account>
     */
    private array $accounts;

    /**
     * @var list<string>
     */
    public array $findAccountIds = [];

    /**
     * @param list<Account> $accounts
     */
    public function __construct(array $accounts = [])
    {
        $this->accounts = $accounts;
    }

    public function save(Account $account): void
    {
        throw new LogicException('save() is not expected in this test.');
    }

    public function get(AccountId $id): Account
    {
        throw new LogicException('get() is not expected in this test.');
    }

    public function find(AccountId $id): ?Account
    {
        $this->findAccountIds[] = $id->value();

        foreach ($this->accounts as $account) {
            if ($account->id()->equals($id)) {
                return $account;
            }
        }

        return null;
    }

    public function findByEmail(Email $email): ?Account
    {
        throw new LogicException('findByEmail() is not expected in this test.');
    }

    public function existsByEmail(Email $email): bool
    {
        throw new LogicException('existsByEmail() is not expected in this test.');
    }

    public function countActiveSystemAdmins(): int
    {
        throw new LogicException('countActiveSystemAdmins() is not expected in this test.');
    }
}
