<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountApprovedMessage;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountApprovedOutboxMessageHandler;
use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use Stringable;

final class AccountApprovedOutboxMessageHandlerTest extends TestCase
{
    private const string EVENT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string ACCOUNT_ID = '018f3f7b-7b07-70c2-ae02-ef5df6170c28';

    public function testHandlerAcceptsAccountApprovedMessage(): void
    {
        $method = new ReflectionMethod(AccountApprovedOutboxMessageHandler::class, '__invoke');
        $parameters = $method->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame(AccountApprovedMessage::class, (string) $parameters[0]->getType());
    }

    public function testHandlerDoesNotDependOnGenericOutboxMessageOrEventTypeFiltering(): void
    {
        $source = file_get_contents(dirname(__DIR__, 5).'/src/Boardly/IdentityAccess/Infrastructure/Outbox/AccountApprovedOutboxMessageHandler.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('App\\Shared\\Infrastructure\\Outbox\\OutboxMessage', $source);
        self::assertStringNotContainsString('__invoke(OutboxMessage', $source);
        self::assertStringNotContainsString('OutboxRecord', $source);
        self::assertStringNotContainsString('event_type', $source);
        self::assertStringNotContainsString('eventType', $source);
        self::assertStringNotContainsString('identity_access.account_approved', $source);
    }

    public function testProcessesConcreteMessageInsideTransaction(): void
    {
        $operationLog = [];
        $transactional = new RecordingTransactional($operationLog);
        $processedMessages = new FakeProcessedMessageStore($transactional, $operationLog);
        $logger = new RecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        self::assertSame([
            'transaction.begin',
            'tryStart',
            'log.info',
            'markProcessed',
            'transaction.commit',
        ], $operationLog);

        self::assertSame([[self::EVENT_ID, AccountApprovedOutboxMessageHandler::class]], $processedMessages->tryStartCalls);
        self::assertSame([[self::EVENT_ID, AccountApprovedOutboxMessageHandler::class]], $processedMessages->markProcessedCalls);
        self::assertCount(1, $logger->records);
        self::assertSame('info', $logger->records[0]['level']);
        self::assertSame('IdentityAccess account approved.', $logger->records[0]['message']);
        self::assertSame([
            'event_id' => self::EVENT_ID,
            'outbox_id' => 'outbox-record-id',
            'account_id' => self::ACCOUNT_ID,
            'approved_at' => '2026-05-03T10:15:30+00:00',
        ], $logger->records[0]['context']);
    }

    public function testDuplicateDeliveryIsSkippedWhenTryStartReturnsFalse(): void
    {
        $operationLog = [];
        $transactional = new RecordingTransactional($operationLog);
        $processedMessages = new FakeProcessedMessageStore($transactional, $operationLog, tryStartResult: false);
        $logger = new RecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        self::assertSame([
            'transaction.begin',
            'tryStart',
            'transaction.commit',
        ], $operationLog);
        self::assertSame([[self::EVENT_ID, AccountApprovedOutboxMessageHandler::class]], $processedMessages->tryStartCalls);
        self::assertSame([], $processedMessages->markProcessedCalls);
        self::assertSame([], $logger->records);
    }

    public function testMarkProcessedIsNotCalledWhenSideEffectThrows(): void
    {
        $operationLog = [];
        $transactional = new RecordingTransactional($operationLog);
        $processedMessages = new FakeProcessedMessageStore($transactional, $operationLog);
        $logger = new RecordingLogger($transactional, $operationLog, throwOnInfo: true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Diagnostic side effect failed.');

        try {
            $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());
        } finally {
            self::assertSame([
                'transaction.begin',
                'tryStart',
                'log.info',
                'transaction.rollback',
            ], $operationLog);
            self::assertSame([[self::EVENT_ID, AccountApprovedOutboxMessageHandler::class]], $processedMessages->tryStartCalls);
            self::assertSame([], $processedMessages->markProcessedCalls);
        }
    }

    public function testLogContextContainsOnlySafeFields(): void
    {
        $operationLog = [];
        $transactional = new RecordingTransactional($operationLog);
        $processedMessages = new FakeProcessedMessageStore($transactional, $operationLog);
        $logger = new RecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        $contextKeys = array_keys($logger->records[0]['context']);
        self::assertSame(['event_id', 'outbox_id', 'account_id', 'approved_at'], $contextKeys);
    }

    public function testApprovedAtIsFormattedAsDateAtom(): void
    {
        $operationLog = [];
        $transactional = new RecordingTransactional($operationLog);
        $processedMessages = new FakeProcessedMessageStore($transactional, $operationLog);
        $logger = new RecordingLogger($transactional, $operationLog);

        $message = $this->message();
        $this->handler($logger, $transactional, $processedMessages)->__invoke($message);

        $approvedAtFromContext = $logger->records[0]['context']['approved_at'];
        $approvedAtFromMessage = $message->approvedAt->format(DATE_ATOM);

        self::assertSame('2026-05-03T10:15:30+00:00', $approvedAtFromContext);
        self::assertSame($approvedAtFromMessage, $approvedAtFromContext);
    }

    public function testUnsafeFieldsAreNotLogged(): void
    {
        $operationLog = [];
        $transactional = new RecordingTransactional($operationLog);
        $processedMessages = new FakeProcessedMessageStore($transactional, $operationLog);
        $logger = new RecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        $encoded = json_encode($logger->records, JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('email', $encoded);
        self::assertStringNotContainsString('password', $encoded);
        self::assertStringNotContainsString('passwordHash', $encoded);
        self::assertStringNotContainsString('accessToken', $encoded);
        self::assertStringNotContainsString('refreshToken', $encoded);
        self::assertStringNotContainsString('refresh_token', $encoded);
        self::assertStringNotContainsString('cookie', $encoded);
        self::assertStringNotContainsString('secret', $encoded);
    }

    private function handler(
        RecordingLogger $logger,
        RecordingTransactional $transactional,
        FakeProcessedMessageStore $processedMessages,
    ): AccountApprovedOutboxMessageHandler {
        return new AccountApprovedOutboxMessageHandler($logger, $transactional, $processedMessages);
    }

    private function message(): AccountApprovedMessage
    {
        return new AccountApprovedMessage(
            outboxId: 'outbox-record-id',
            eventId: self::EVENT_ID,
            accountId: self::ACCOUNT_ID,
            approvedAt: new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
        );
    }
}

final class RecordingTransactional implements TransactionalInterface
{
    public bool $isActive = false;

    /**
     * @var list<string>
     */
    private array $operationLog;

    /**
     * @param list<string> $operationLog
     */
    public function __construct(array &$operationLog)
    {
        $this->operationLog = &$operationLog;
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transactional(callable $operation): mixed
    {
        $this->recordOperation('transaction.begin');
        $this->isActive = true;

        try {
            $result = $operation();
        } catch (\Throwable $throwable) {
            $this->isActive = false;
            $this->recordOperation('transaction.rollback');

            throw $throwable;
        }

        $this->isActive = false;
        $this->recordOperation('transaction.commit');

        return $result;
    }

    private function recordOperation(string $operation): void
    {
        $operationLog = $this->operationLog;
        $operationLog[] = $operation;
        $this->operationLog = $operationLog;
    }
}

final class FakeProcessedMessageStore implements ProcessedMessageStoreInterface
{
    /**
     * @var list<array{0: string, 1: string}>
     */
    public array $tryStartCalls = [];

    /**
     * @var list<array{0: string, 1: string}>
     */
    public array $markProcessedCalls = [];

    /**
     * @param list<string> $operationLog
     */
    public function __construct(
        private readonly RecordingTransactional $transactional,
        array &$operationLog,
        private readonly bool $tryStartResult = true,
    ) {
        $this->operationLog = &$operationLog;
    }

    /**
     * @var list<string>
     */
    private array $operationLog;

    public function tryStart(string $eventId, string $handlerName): bool
    {
        TestCase::assertTrue($this->transactional->isActive, 'tryStart must run inside the transaction.');

        $this->recordOperation('tryStart');
        $this->tryStartCalls[] = [$eventId, $handlerName];

        return $this->tryStartResult;
    }

    public function markProcessed(string $eventId, string $handlerName): void
    {
        TestCase::assertTrue($this->transactional->isActive, 'markProcessed must run inside the transaction.');

        $this->recordOperation('markProcessed');
        $this->markProcessedCalls[] = [$eventId, $handlerName];
    }

    private function recordOperation(string $operation): void
    {
        $operationLog = $this->operationLog;
        $operationLog[] = $operation;
        $this->operationLog = $operationLog;
    }
}

final class RecordingLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string, context: array<string, mixed>}>
     */
    public array $records = [];

    /**
     * @param list<string> $operationLog
     */
    public function __construct(
        private readonly RecordingTransactional $transactional,
        array &$operationLog,
        private readonly bool $throwOnInfo = false,
    ) {
        $this->operationLog = &$operationLog;
    }

    /**
     * @var list<string>
     */
    private array $operationLog;

    /**
     * @param array<string, mixed> $context
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        TestCase::assertTrue($this->transactional->isActive, 'diagnostic logging must run inside the transaction.');

        $this->recordOperation(sprintf('log.%s', (string) $level));

        if ($this->throwOnInfo && 'info' === $level) {
            throw new \RuntimeException('Diagnostic side effect failed.');
        }

        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    private function recordOperation(string $operation): void
    {
        $operationLog = $this->operationLog;
        $operationLog[] = $operation;
        $this->operationLog = $operationLog;
    }
}
