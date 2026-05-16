<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectDeletedMessage;
use App\Boardly\Projects\Infrastructure\Outbox\ProjectDeletedOutboxMessageMapper;
use App\Shared\Infrastructure\Outbox\OutboxRecord;
use PHPUnit\Framework\TestCase;

final class ProjectDeletedOutboxMessageMapperTest extends TestCase
{
    private const string PROJECT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsProjectDeletedOutboxRecord(): void
    {
        self::assertTrue((new ProjectDeletedOutboxMessageMapper())->supports($this->outboxRecord()));
    }

    public function testDoesNotSupportAnotherEventType(): void
    {
        self::assertFalse((new ProjectDeletedOutboxMessageMapper())->supports(
            $this->outboxRecord(eventType: 'projects.project_created'),
        ));
    }

    public function testMapsProjectDeletedOutboxRecord(): void
    {
        $message = (new ProjectDeletedOutboxMessageMapper())->map($this->outboxRecord());

        self::assertInstanceOf(ProjectDeletedMessage::class, $message);
        self::assertSame('outbox-record-id', $message->outboxId);
        self::assertSame('event-id', $message->eventId);
        self::assertSame(self::PROJECT_ID, $message->projectId);
        self::assertEquals(new \DateTimeImmutable('2026-05-16T14:00:00+00:00'), $message->deletedAt);
    }

    public function testRejectsMissingProjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "project_id" must be a non-empty string');

        (new ProjectDeletedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'deleted_at' => '2026-05-16T14:00:00+00:00',
        ]));
    }

    public function testRejectsAggregateIdMismatchWhenAggregateIdIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('aggregateId "different-project-id" must match payload project_id');

        (new ProjectDeletedOutboxMessageMapper())->map($this->outboxRecord(aggregateId: 'different-project-id'));
    }

    public function testRejectsMissingDeletedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "deleted_at" must be a valid date/time string');

        (new ProjectDeletedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'project_id' => self::PROJECT_ID,
        ]));
    }

    private function outboxRecord(
        string $eventType = 'projects.project_deleted',
        ?string $aggregateId = self::PROJECT_ID,
        ?array $payload = null,
    ): OutboxRecord {
        $now = new \DateTimeImmutable('2026-05-16T14:30:00+00:00');

        return new OutboxRecord(
            id: 'outbox-record-id',
            eventId: 'event-id',
            eventType: $eventType,
            aggregateType: 'projects.project',
            aggregateId: $aggregateId,
            payload: $payload ?? [
                'project_id' => self::PROJECT_ID,
                'deleted_at' => '2026-05-16T14:00:00+00:00',
            ],
            occurredAt: $now,
            availableAt: $now,
            publishedAt: null,
            attempts: 0,
            lastError: null,
            createdAt: $now,
        );
    }
}
