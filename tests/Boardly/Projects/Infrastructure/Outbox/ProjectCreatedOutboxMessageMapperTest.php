<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Infrastructure\Message\ProjectCreatedMessage;
use App\Boardly\Projects\Infrastructure\Outbox\ProjectCreatedOutboxMessageMapper;
use App\Shared\Infrastructure\Outbox\OutboxRecord;
use PHPUnit\Framework\TestCase;

final class ProjectCreatedOutboxMessageMapperTest extends TestCase
{
    private const string PROJECT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsProjectCreatedOutboxRecord(): void
    {
        self::assertTrue((new ProjectCreatedOutboxMessageMapper())->supports($this->outboxRecord()));
    }

    public function testDoesNotSupportAnotherEventType(): void
    {
        self::assertFalse((new ProjectCreatedOutboxMessageMapper())->supports(
            $this->outboxRecord(eventType: 'projects.project_archived'),
        ));
    }

    public function testMapsProjectCreatedOutboxRecord(): void
    {
        $message = (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord());

        self::assertInstanceOf(ProjectCreatedMessage::class, $message);
        self::assertSame('outbox-record-id', $message->outboxId);
        self::assertSame('event-id', $message->eventId);
        self::assertSame(self::PROJECT_ID, $message->projectId);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e', $message->ownerAccountId);
        self::assertSame('Project Alpha', $message->name);
        self::assertSame('rocket', $message->iconKey);
        self::assertEquals(new \DateTimeImmutable('2026-05-03T10:15:30+00:00'), $message->createdAt);
    }

    public function testRejectsMissingProjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "project_id" must be a non-empty string');

        (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'owner_account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            'name' => 'Project Alpha',
            'icon_key' => 'rocket',
            'created_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsAggregateIdMismatchWhenAggregateIdIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('aggregateId "different-project-id" must match payload project_id');

        (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord(aggregateId: 'different-project-id'));
    }

    public function testRejectsMissingOwnerAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "owner_account_id" must be a non-empty string');

        (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'project_id' => self::PROJECT_ID,
            'name' => 'Project Alpha',
            'icon_key' => 'rocket',
            'created_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsMissingName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "name" must be a non-empty string');

        (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'project_id' => self::PROJECT_ID,
            'owner_account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            'icon_key' => 'rocket',
            'created_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsMissingIconKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "icon_key" must be a non-empty string');

        (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'project_id' => self::PROJECT_ID,
            'owner_account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            'name' => 'Project Alpha',
            'created_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsMissingCreatedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "created_at" must be a valid date/time string');

        (new ProjectCreatedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'project_id' => self::PROJECT_ID,
            'owner_account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            'name' => 'Project Alpha',
            'icon_key' => 'rocket',
        ]));
    }

    private function outboxRecord(
        string $eventType = 'projects.project_created',
        ?string $aggregateId = self::PROJECT_ID,
        ?array $payload = null,
    ): OutboxRecord {
        $now = new \DateTimeImmutable('2026-05-04T10:00:00+00:00');

        return new OutboxRecord(
            id: 'outbox-record-id',
            eventId: 'event-id',
            eventType: $eventType,
            aggregateType: 'projects.project',
            aggregateId: $aggregateId,
            payload: $payload ?? [
                'project_id' => self::PROJECT_ID,
                'owner_account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
                'name' => 'Project Alpha',
                'icon_key' => 'rocket',
                'created_at' => '2026-05-03T10:15:30+00:00',
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
