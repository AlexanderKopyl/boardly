<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Application\CreateSystemAdmin;

use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminCommand;
use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminHandler;
use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminResult;
use App\Boardly\IdentityAccess\Application\Exception\EmailAlreadyRegistered;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Application\Outbox\OutboxInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Port\IdGeneratorInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

final class CreateSystemAdminHandlerTest extends TestCase
{
    private const string GENERATED_ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string VALID_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testSuccessfulSystemAdminCreationSavesActiveSystemAdminAccount(): void
    {
        $repository = new FakeAccountRepository();
        $outbox = new FakeOutbox();
        $clockTime = new DateTimeImmutable('2026-05-07T08:30:00+00:00');

        $result = $this->handler(
            $repository,
            new FakePasswordHasher(self::VALID_HASH),
            new FakeClock($clockTime),
            new FakeIdGenerator(self::GENERATED_ACCOUNT_ID),
            outbox: $outbox,
        )->__invoke(new CreateSystemAdminCommand(
            '  System.Admin@Example.COM  ',
            'correct horse battery staple',
            '  System Admin  ',
        ));

        self::assertInstanceOf(CreateSystemAdminResult::class, $result);
        self::assertSame(self::GENERATED_ACCOUNT_ID, $result->accountId());
        self::assertSame('system.admin@example.com', $result->email());
        self::assertSame('active', $result->status());
        self::assertTrue($result->isSystemAdmin());
        self::assertCount(1, $repository->savedAccounts);

        $account = $repository->savedAccounts[0];
        self::assertSame('active', $account->status()->value());
        self::assertTrue($account->isSystemAdmin());
        self::assertSame('system.admin@example.com', $account->email()->value());
        self::assertSame('System Admin', $account->name()->value());
        self::assertSame($clockTime, $account->createdAt());
        self::assertSame($clockTime, $account->updatedAt());
        self::assertSame($clockTime, $account->approvedAt());

        self::assertCount(1, $outbox->storedEvents);
        self::assertInstanceOf(AccountRegistered::class, $outbox->storedEvents[0]);
        self::assertSame(self::GENERATED_ACCOUNT_ID, $outbox->storedEvents[0]->accountId()->value());
        self::assertSame('system.admin@example.com', $outbox->storedEvents[0]->email()->value());
        self::assertTrue($outbox->storedEvents[0]->isSystemAdmin());
        self::assertSame($clockTime, $outbox->storedEvents[0]->registeredAt());
    }

    public function testDuplicateNormalizedEmailThrowsEmailAlreadyRegistered(): void
    {
        $repository = new FakeAccountRepository(['system.admin@example.com']);
        $hasher = new FakePasswordHasher(self::VALID_HASH);
        $idGenerator = new FakeIdGenerator(self::GENERATED_ACCOUNT_ID);
        $outbox = new FakeOutbox();

        $this->expectException(EmailAlreadyRegistered::class);

        try {
            $this->handler(
                $repository,
                $hasher,
                new FakeClock(new DateTimeImmutable('2026-05-07T08:30:00+00:00')),
                $idGenerator,
                outbox: $outbox,
            )->__invoke(new CreateSystemAdminCommand(
                '  System.Admin@Example.COM ',
                'plain-password',
                'System Admin',
            ));
        } finally {
            self::assertSame([], $hasher->receivedPlainPasswords);
            self::assertSame(0, $idGenerator->generateCallCount);
            self::assertSame([], $repository->savedAccounts);
            self::assertSame([], $outbox->storedEvents);
        }
    }

    public function testPasswordIsHashedBeforeSave(): void
    {
        $repository = new FakeAccountRepository();
        $hasher = new FakePasswordHasher(self::VALID_HASH);

        $this->handler(
            $repository,
            $hasher,
            new FakeClock(new DateTimeImmutable('2026-05-07T08:30:00+00:00')),
            new FakeIdGenerator(self::GENERATED_ACCOUNT_ID),
        )->__invoke(new CreateSystemAdminCommand(
            'system.admin@example.com',
            'plain-password',
            'System Admin',
        ));

        self::assertSame(['plain-password'], $hasher->receivedPlainPasswords);
        self::assertCount(1, $repository->savedAccounts);
        self::assertSame(self::VALID_HASH, $repository->savedAccounts[0]->passwordHash()->value());
        self::assertNotSame('plain-password', $repository->savedAccounts[0]->passwordHash()->value());
    }

    public function testAccountSaveAndOutboxStoreRunInsideSameTransactionInOrder(): void
    {
        $transactional = new FakeTransactional();
        $operationLog = new FakeOperationLog();
        $repository = new FakeAccountRepository(transactional: $transactional, operationLog: $operationLog);
        $outbox = new FakeOutbox($transactional, $operationLog);

        $this->handler(
            $repository,
            new FakePasswordHasher(self::VALID_HASH),
            new FakeClock(new DateTimeImmutable('2026-05-07T08:30:00+00:00')),
            new FakeIdGenerator(self::GENERATED_ACCOUNT_ID),
            $transactional,
            $outbox,
        )->__invoke(new CreateSystemAdminCommand(
            'system.admin@example.com',
            'plain-password',
            'System Admin',
        ));

        self::assertSame(1, $transactional->transactionCallCount);
        self::assertSame(['save', 'outbox'], $operationLog->operations);
        self::assertSame([true], $repository->saveInsideTransaction);
        self::assertSame([true], $outbox->storeInsideTransaction);
    }

    public function testResultDoesNotExposePlainPasswordOrPasswordHash(): void
    {
        $reflection = new ReflectionClass(CreateSystemAdminResult::class);
        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor);
        self::assertSame(['accountId', 'email', 'status', 'isSystemAdmin'], array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getName(),
            $constructor->getParameters(),
        ));

        $publicMethodNames = array_map(
            static fn (ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        sort($publicMethodNames);
        self::assertSame(['__construct', 'accountId', 'email', 'isSystemAdmin', 'status'], $publicMethodNames);

        foreach (['plainPassword', 'password', 'passwordHash', 'hash', 'account', 'entity'] as $unsafeMethod) {
            self::assertNotContains($unsafeMethod, $publicMethodNames, sprintf(
                'CreateSystemAdminResult must not expose %s().',
                $unsafeMethod,
            ));
        }
    }

    private function handler(
        FakeAccountRepository $repository,
        FakePasswordHasher $hasher,
        FakeClock $clock,
        FakeIdGenerator $idGenerator,
        ?FakeTransactional $transactional = null,
        ?FakeOutbox $outbox = null,
    ): CreateSystemAdminHandler {
        return new CreateSystemAdminHandler(
            $repository,
            $hasher,
            $clock,
            $idGenerator,
            $transactional ?? new FakeTransactional(),
            $outbox ?? new FakeOutbox(),
        );
    }
}

final class FakeAccountRepository implements AccountRepositoryInterface
{
    /**
     * @var list<Account>
     */
    public array $savedAccounts = [];

    /**
     * @var list<bool>
     */
    public array $saveInsideTransaction = [];

    /**
     * @param list<string> $existingNormalizedEmails
     */
    public function __construct(
        private readonly array $existingNormalizedEmails = [],
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
        throw new LogicException('FakeAccountRepository::findByEmail() is not used by these tests.');
    }

    public function existsByEmail(Email $email): bool
    {
        return in_array($email->value(), $this->existingNormalizedEmails, true);
    }

    public function countActiveSystemAdmins(): int
    {
        throw new LogicException('FakeAccountRepository::countActiveSystemAdmins() is not used by these tests.');
    }
}

final class FakePasswordHasher implements PasswordHasherInterface
{
    /**
     * @var list<string>
     */
    public array $receivedPlainPasswords = [];

    public function __construct(
        private readonly string $hash,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        $this->receivedPlainPasswords[] = $plainPassword;

        return $this->hash;
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

    public function __construct(
        private readonly string $id,
    ) {
    }

    public function generate(): string
    {
        ++$this->generateCallCount;

        return $this->id;
    }
}

final class FakeOutbox implements OutboxInterface
{
    /**
     * @var list<DomainEvent>
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

    /**
     * @param list<DomainEvent> $events
     */
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
