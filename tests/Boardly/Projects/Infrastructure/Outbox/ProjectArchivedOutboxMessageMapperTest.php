<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectArchivedMessage;
use App\Boardly\Projects\Infrastructure\Outbox\ProjectArchivedOutboxMessageMapper;
use App\Shared\Infrastructure\Outbox\OutboxRecord;
use PHPUnit\Framework\TestCase;

final class ProjectArchivedOutboxMessageMapperTest extends TestCase
{
    private const string PROJECT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsProjectArchivedOutboxRecord(): void
    {
        self::assertTrue((new ProjectArchivedOutboxMessageMapper())->supports($this->outboxRecord()));
    }

    public function testDoesNotSupportAnotherEventType(): void
    {
        self::assertFalse((new ProjectArchivedOutboxMessageMapper())->supports(
            $this->outboxRecord(eventType: 'projects.project_deleted'),
        ));
    }

    public function testMapsProjectArchivedOutboxRecord(): void
    {
        $message = (new ProjectArchivedOutboxMessageMapper())->map($this->outboxRecord());

        self::assertInstanceOf(ProjectArchivedMessage::class, $message);
        self::assertSame('outbox-record-id', $message->outboxId);
        self::assertSame('event-id', $message->eventId);
        self::assertSame(self::PROJECT_ID, $message->projectId);
        self::assertEquals(new \DateTimeImmutable('2026-05-07T09:10:00+00:00'), $message->archivedAt);
    }

    public function testRejectsMissingProjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "project_id" must be a non-empty string');

        (new ProjectArchivedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'archived_at' => '2026-05-07T09:10:00+00:00',
        ]));
    }

    public function testRejectsAggregateIdMismatchWhenAggregateIdIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('aggregateId "different-project-id" must match payload project_id');

        (new ProjectArchivedOutboxMessageMapper())->map($this->outboxRecord(aggregateId: 'different-project-id'));
    }

    public function testRejectsMissingArchivedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "archived_at" must be a valid date/time string');

        (new ProjectArchivedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'project_id' => self::PROJECT_ID,
        ]));
    }

    private function outboxRecord(
        string $eventType = 'projects.project_archived',
        ?string $aggregateId = self::PROJECT_ID,
        ?array $payload = null,
    ): OutboxRecord {
        $now = new \DateTimeImmutable('2026-05-07T10:00:00+00:00');

        return new OutboxRecord(
            id: 'outbox-record-id',
            eventId: 'event-id',
            eventType: $eventType,
            aggregateType: 'projects.project',
            aggregateId: $aggregateId,
            payload: $payload ?? [
                'project_id' => self::PROJECT_ID,
                'archived_at' => '2026-05-07T09:10:00+00:00',
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
