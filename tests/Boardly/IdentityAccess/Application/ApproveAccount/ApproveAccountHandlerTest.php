<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Application\ApproveAccount;

use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountCommand;
use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountHandler;
use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountResult;
use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyActive;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class ApproveAccountHandlerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string VALID_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testSuccessfulPendingAccountApproval(): void
    {
        $account = $this->pendingAccount('registered.account@example.com');
        $repository = new FakeAccountRepository([$account]);
        $outbox = new FakeOutbox();
        $approvedAt = new DateTimeImmutable('2026-05-07T09:10:00+00:00');

        $result = $this->handler(
            $repository,
            new FakeClock($approvedAt),
            outbox: $outbox,
        )->__invoke(new ApproveAccountCommand('  Registered.Account@Example.COM  '));

        self::assertInstanceOf(ApproveAccountResult::class, $result);
        self::assertSame(self::ACCOUNT_ID, $result->accountId());
        self::assertSame('registered.account@example.com', $result->email());
        self::assertSame('active', $result->status());
        self::assertSame($approvedAt, $result->approvedAt());
        self::assertCount(1, $repository->savedAccounts);

        $savedAccount = $repository->savedAccounts[0];
        self::assertSame('active', $savedAccount->status()->value());
        self::assertSame($approvedAt, $savedAccount->approvedAt());
        self::assertSame($approvedAt, $savedAccount->updatedAt());

        self::assertCount(1, $outbox->storedEvents);
        self::assertInstanceOf(AccountApproved::class, $outbox->storedEvents[0]);
        self::assertSame(self::ACCOUNT_ID, $outbox->storedEvents[0]->accountId()->value());
        self::assertSame($approvedAt, $outbox->storedEvents[0]->approvedAt());
    }

    public function testApprovalOfMissingAccountFails(): void
    {
        $repository = new FakeAccountRepository();
        $outbox = new FakeOutbox();

        $this->expectException(AccountNotFound::class);
        $this->expectExceptionMessage('Account was not found.');

        try {
            $this->handler(
                $repository,
                new FakeClock(new DateTimeImmutable('2026-05-07T09:10:00+00:00')),
                outbox: $outbox,
            )->__invoke(new ApproveAccountCommand(' missing@example.com '));
        } finally {
            self::assertSame(['missing@example.com'], $repository->findByEmailValues);
            self::assertSame([], $repository->savedAccounts);
            self::assertSame([], $outbox->storedEvents);
        }
    }

    public function testInvalidApprovalTransitionIsRejectedByDomainRules(): void
    {
        $activeAccount = $this->activeSystemAdmin('active@example.com');
        $repository = new FakeAccountRepository([$activeAccount]);
        $outbox = new FakeOutbox();

        $this->expectException(AccountAlreadyActive::class);

        try {
            $this->handler(
                $repository,
                new FakeClock(new DateTimeImmutable('2026-05-07T09:10:00+00:00')),
                outbox: $outbox,
            )->__invoke(new ApproveAccountCommand('active@example.com'));
        } finally {
            self::assertSame([], $repository->savedAccounts);
            self::assertSame([], $outbox->storedEvents);
        }
    }

    public function testAccountSaveAndOutboxStoreRunInsideSameTransactionInOrder(): void
    {
        $transactional = new FakeTransactional();
        $operationLog = new FakeOperationLog();
        $repository = new FakeAccountRepository(
            [$this->pendingAccount('registered@example.com')],
            $transactional,
            $operationLog,
        );
        $outbox = new FakeOutbox($transactional, $operationLog);

        $this->handler(
            $repository,
            new FakeClock(new DateTimeImmutable('2026-05-07T09:10:00+00:00')),
            $transactional,
            $outbox,
        )->__invoke(new ApproveAccountCommand('registered@example.com'));

        self::assertSame(1, $transactional->transactionCallCount);
        self::assertSame(['save', 'outbox'], $operationLog->operations);
        self::assertSame([true], $repository->saveInsideTransaction);
        self::assertSame([true], $outbox->storeInsideTransaction);
    }

    public function testResultExposesOnlySafeOutput(): void
    {
        $reflection = new ReflectionClass(ApproveAccountResult::class);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);
        self::assertSame(['accountId', 'email', 'status', 'approvedAt'], array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $constructor->getParameters(),
        ));

        $publicMethodNames = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        sort($publicMethodNames);
        self::assertSame(['__construct', 'accountId', 'approvedAt', 'email', 'status'], $publicMethodNames);

        foreach (
            [
                'plainPassword',
                'password',
                'passwordHash',
                'accessToken',
                'refreshToken',
                'cookie',
                'token',
                'account',
                'entity',
            ] as $unsafeMethod
        ) {
            self::assertNotContains($unsafeMethod, $publicMethodNames, sprintf(
                'ApproveAccountResult must not expose %s().',
                $unsafeMethod,
            ));
        }
    }

    private function handler(
        FakeAccountRepository $repository,
        FakeClock $clock,
        ?FakeTransactional $transactional = null,
        ?FakeOutbox $outbox = null,
    ): ApproveAccountHandler {
        return new ApproveAccountHandler(
            $repository,
            $clock,
            $transactional ?? new FakeTransactional(),
            $outbox ?? new FakeOutbox(),
        );
    }

    private function pendingAccount(string $email): Account
    {
        return Account::register(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString($email),
            PasswordHash::fromString(self::VALID_HASH),
            AccountName::fromString('Registered Account'),
            new DateTimeImmutable('2026-05-07T08:00:00+00:00'),
        )->account();
    }

    private function activeSystemAdmin(string $email): Account
    {
        return Account::createSystemAdmin(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString($email),
            PasswordHash::fromString(self::VALID_HASH),
            AccountName::fromString('System Admin'),
            new DateTimeImmutable('2026-05-07T08:00:00+00:00'),
        )->account();
    }
}

final class FakeAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var list<Account>
     */
    public array $savedAccounts = [];

    /**
     * @var list<string>
     */
    public array $findByEmailValues = [];

    /**
     * @var list<bool>
     */
    public array $saveInsideTransaction = [];

    /**
     * @param list<Account> $accounts
     */
    public function __construct(
        private readonly array $accounts = [],
        private readonly ?FakeTransactional $transactional = null,
        private readonly ?FakeOperationLog $operationLog = null,
    ) {
    }

    public function save(Account $account): void
    {
        $this->operationLog?->record('save');
        $this->saveInsideTransaction[] = $this->transactional !== null && $this->transactional->isInsideTransaction;
        $this->savedAccounts[] = $account;
    }

    public function get(AccountId $id): Account
    {
        throw new LogicException('FakeAccountRepository::get() is not used by these tests.');
    }

    public function find(AccountId $id): ?Account
    {
        throw new LogicException('FakeAccountRepository::find() is not used by these tests.');
    }

    public function findByEmail(Email $email): ?Account
    {
        $this->findByEmailValues[] = $email->value();

        foreach ($this->accounts as $account) {
            if ($account->email()->equals($email)) {
                return $account;
            }
        }

        return null;
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

final class FakeOutbox implements OutboxInterface
{
    /**
     * @var list<object>
     */
    public array $storedEvents = [];

    /**
     * @var list<bool>
     */
    public array $storeInsideTransaction = [];

    public function __construct(
        private readonly ?FakeTransactional $transactional = null,
        private readonly ?FakeOperationLog $operationLog = null,
    ) {
    }

    public function store(array $events): void
    {
        $this->operationLog?->record('outbox');
        $this->storeInsideTransaction[] = $this->transactional !== null && $this->transactional->isInsideTransaction;

        foreach ($events as $event) {
            $this->storedEvents[] = $event;
        }
    }
}

final class FakeOperationLog
{
    /**
     * @var list<string>
     */
    public array $operations = [];

    public function record(string $operation): void
    {
        $this->operations[] = $operation;
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
