<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectCreatedMessage;
use App\Boardly\Projects\Infrastructure\Outbox\ProjectCreatedOutboxMessageHandler;
use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use App\Shared\Application\Transaction\TransactionalInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use Stringable;

final class ProjectCreatedOutboxMessageHandlerTest extends TestCase
{
    private const string EVENT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string PROJECT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e';

    public function testHandlerAcceptsProjectCreatedMessage(): void
    {
        $method = new ReflectionMethod(ProjectCreatedOutboxMessageHandler::class, '__invoke');
        $parameters = $method->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame(ProjectCreatedMessage::class, (string) $parameters[0]->getType());
    }

    public function testHandlerDoesNotDependOnGenericOutboxMessageOrEventTypeFiltering(): void
    {
        $source = file_get_contents(dirname(__DIR__, 5).'/src/Boardly/Projects/Infrastructure/Outbox/ProjectCreatedOutboxMessageHandler.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('App\\Shared\\Infrastructure\\Outbox\\OutboxMessage', $source);
        self::assertStringNotContainsString('__invoke(OutboxMessage', $source);
        self::assertStringNotContainsString('OutboxRecord', $source);
        self::assertStringNotContainsString('event_type', $source);
        self::assertStringNotContainsString('eventType', $source);
        self::assertStringNotContainsString('projects.project_created', $source);
    }

    public function testProcessesConcreteMessageInsideTransaction(): void
    {
        $operationLog = [];
        $transactional = new ProjectCreatedRecordingTransactional($operationLog);
        $processedMessages = new ProjectCreatedFakeProcessedMessageStore($transactional, $operationLog);
        $logger = new ProjectCreatedRecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        self::assertSame([
            'transaction.begin',
            'tryStart',
            'log.info',
            'markProcessed',
            'transaction.commit',
        ], $operationLog);

        self::assertSame([[self::EVENT_ID, ProjectCreatedOutboxMessageHandler::class]], $processedMessages->tryStartCalls);
        self::assertSame([[self::EVENT_ID, ProjectCreatedOutboxMessageHandler::class]], $processedMessages->markProcessedCalls);
        self::assertCount(1, $logger->records);
        self::assertSame('info', $logger->records[0]['level']);
        self::assertSame('ProjectCreated outbox message consumed.', $logger->records[0]['message']);
        self::assertSame([
            'outbox_id' => 'outbox-record-id',
            'event_id' => self::EVENT_ID,
            'project_id' => self::PROJECT_ID,
            'owner_account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f',
            'name' => 'Project Alpha',
            'icon_key' => 'rocket',
            'created_at' => '2026-05-03T10:15:30+00:00',
        ], $logger->records[0]['context']);
    }

    public function testDuplicateDeliveryIsSkippedWhenTryStartReturnsFalse(): void
    {
        $operationLog = [];
        $transactional = new ProjectCreatedRecordingTransactional($operationLog);
        $processedMessages = new ProjectCreatedFakeProcessedMessageStore($transactional, $operationLog, tryStartResult: false);
        $logger = new ProjectCreatedRecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        self::assertSame([
            'transaction.begin',
            'tryStart',
            'transaction.commit',
        ], $operationLog);
        self::assertSame([[self::EVENT_ID, ProjectCreatedOutboxMessageHandler::class]], $processedMessages->tryStartCalls);
        self::assertSame([], $processedMessages->markProcessedCalls);
        self::assertSame([], $logger->records);
    }

    public function testMarkProcessedIsNotCalledWhenSideEffectThrows(): void
    {
        $operationLog = [];
        $transactional = new ProjectCreatedRecordingTransactional($operationLog);
        $processedMessages = new ProjectCreatedFakeProcessedMessageStore($transactional, $operationLog);
        $logger = new ProjectCreatedRecordingLogger($transactional, $operationLog, throwOnInfo: true);

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
            self::assertSame([[self::EVENT_ID, ProjectCreatedOutboxMessageHandler::class]], $processedMessages->tryStartCalls);
            self::assertSame([], $processedMessages->markProcessedCalls);
        }
    }

    public function testLogContextContainsOnlySafeFields(): void
    {
        $operationLog = [];
        $transactional = new ProjectCreatedRecordingTransactional($operationLog);
        $processedMessages = new ProjectCreatedFakeProcessedMessageStore($transactional, $operationLog);
        $logger = new ProjectCreatedRecordingLogger($transactional, $operationLog);

        $this->handler($logger, $transactional, $processedMessages)->__invoke($this->message());

        self::assertSame(['outbox_id', 'event_id', 'project_id', 'owner_account_id', 'name', 'icon_key', 'created_at'], array_keys($logger->records[0]['context']));
    }

    private function handler(
        ProjectCreatedRecordingLogger $logger,
        ProjectCreatedRecordingTransactional $transactional,
        ProjectCreatedFakeProcessedMessageStore $processedMessages,
    ): ProjectCreatedOutboxMessageHandler {
        return new ProjectCreatedOutboxMessageHandler($logger, $transactional, $processedMessages);
    }

    private function message(): ProjectCreatedMessage
    {
        return new ProjectCreatedMessage(
            outboxId: 'outbox-record-id',
            eventId: self::EVENT_ID,
            projectId: self::PROJECT_ID,
            ownerAccountId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f',
            name: 'Project Alpha',
            iconKey: 'rocket',
            createdAt: new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
        );
    }
}

final class ProjectCreatedRecordingTransactional implements TransactionalInterface
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

final class ProjectCreatedFakeProcessedMessageStore implements ProcessedMessageStoreInterface
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
        private readonly ProjectCreatedRecordingTransactional $transactional,
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

final class ProjectCreatedRecordingLogger extends AbstractLogger
{
    /**
     * @var list<array{level: string, message: string, context: array<string, mixed>}>
     */
    public array $records = [];

    /**
     * @param list<string> $operationLog
     */
    public function __construct(
        private readonly ProjectCreatedRecordingTransactional $transactional,
        array &$operationLog,
        private readonly bool $throwOnInfo = false,
    ) {
        $this->operationLog = &$operationLog;
    }

    /**
     * @var list<string>
     */
    private array $operationLog;

    public function log($level, Stringable|string $message, array $context = []): void
    {
        TestCase::assertTrue($this->transactional->isActive, 'Logger must run inside the transaction.');

        $this->recordOperation('log.'.(string) $level);

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
