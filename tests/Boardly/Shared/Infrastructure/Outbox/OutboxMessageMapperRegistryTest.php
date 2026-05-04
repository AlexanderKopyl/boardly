<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface;
use App\Shared\Infrastructure\Outbox\OutboxMessageMapperRegistry;
use App\Shared\Infrastructure\Outbox\OutboxRecord;
use PHPUnit\Framework\TestCase;

final class OutboxMessageMapperRegistryTest extends TestCase
{
    public function testDelegatesToMatchingMapper(): void
    {
        $record = $this->outboxRecord();
        $mappedMessage = new TestMappedOutboxMessage('mapped-message');
        $nonMatching = new TestOutboxMessageMapper(false, new TestMappedOutboxMessage('not-used'));
        $matching = new TestOutboxMessageMapper(true, $mappedMessage);

        $result = (new OutboxMessageMapperRegistry([$nonMatching, $matching]))->map($record);

        self::assertSame($mappedMessage, $result);
        self::assertSame([$record], $nonMatching->supportedRecords);
        self::assertSame([], $nonMatching->mappedRecords);
        self::assertSame([$record], $matching->supportedRecords);
        self::assertSame([$record], $matching->mappedRecords);
    }

    public function testChecksMappersInOrderAndStopsAfterFirstMatch(): void
    {
        $record = $this->outboxRecord();
        $first = new TestOutboxMessageMapper(false, new TestMappedOutboxMessage('first'));
        $second = new TestOutboxMessageMapper(true, new TestMappedOutboxMessage('second'));
        $third = new TestOutboxMessageMapper(true, new TestMappedOutboxMessage('third'));

        $result = (new OutboxMessageMapperRegistry([$first, $second, $third]))->map($record);

        self::assertSame('second', $result->name);
        self::assertSame([$record], $first->supportedRecords);
        self::assertSame([$record], $second->supportedRecords);
        self::assertSame([], $third->supportedRecords);
        self::assertSame([], $first->mappedRecords);
        self::assertSame([$record], $second->mappedRecords);
        self::assertSame([], $third->mappedRecords);
    }

    public function testSkipsNonMatchingMappers(): void
    {
        $record = $this->outboxRecord();
        $first = new TestOutboxMessageMapper(false, new TestMappedOutboxMessage('first'));
        $second = new TestOutboxMessageMapper(false, new TestMappedOutboxMessage('second'));
        $third = new TestOutboxMessageMapper(true, new TestMappedOutboxMessage('third'));

        $result = (new OutboxMessageMapperRegistry([$first, $second, $third]))->map($record);

        self::assertSame('third', $result->name);
        self::assertSame([], $first->mappedRecords);
        self::assertSame([], $second->mappedRecords);
        self::assertSame([$record], $third->mappedRecords);
    }

    public function testUnsupportedRecordThrowsClearException(): void
    {
        $record = $this->outboxRecord(eventType: 'identity_access.unknown');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported outbox record event type "identity_access.unknown".');

        (new OutboxMessageMapperRegistry([
            new TestOutboxMessageMapper(false, new TestMappedOutboxMessage('not-used')),
        ]))->map($record);
    }

    public function testRegistryDoesNotRequireIdentityAccessDependencies(): void
    {
        $registry = new OutboxMessageMapperRegistry([]);

        self::assertInstanceOf(OutboxMessageMapperRegistry::class, $registry);
    }

    private function outboxRecord(string $eventType = 'test.event'): OutboxRecord
    {
        $now = new \DateTimeImmutable('2026-05-04T10:00:00+00:00');

        return new OutboxRecord(
            id: 'outbox-record-id',
            eventId: 'event-id',
            eventType: $eventType,
            aggregateType: 'test.aggregate',
            aggregateId: 'test-aggregate-id',
            payload: ['value' => 'test'],
            occurredAt: $now,
            availableAt: $now,
            publishedAt: null,
            attempts: 0,
            lastError: null,
            createdAt: $now,
        );
    }
}

final readonly class TestMappedOutboxMessage
{
    public function __construct(
        public string $name,
    ) {
    }
}

final class TestOutboxMessageMapper implements OutboxMessageMapperInterface
{
    /**
     * @var list<OutboxRecord>
     */
    public array $supportedRecords = [];

    /**
     * @var list<OutboxRecord>
     */
    public array $mappedRecords = [];

    public function __construct(
        private readonly bool $supports,
        private readonly object $mappedMessage,
    ) {
    }

    public function supports(OutboxRecord $record): bool
    {
        $this->supportedRecords[] = $record;

        return $this->supports;
    }

    public function map(OutboxRecord $record): object
    {
        $this->mappedRecords[] = $record;

        return $this->mappedMessage;
    }
}
