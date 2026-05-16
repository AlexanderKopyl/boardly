<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Domain\Event\ProjectDeleted;
use App\Boardly\Projects\Infrastructure\Outbox\ProjectDeletedOutboxEventSerializer;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectDeletedOutboxEventSerializerTest extends TestCase
{
    public function testSupportsProjectDeletedEvents(): void
    {
        $serializer = new ProjectDeletedOutboxEventSerializer();

        self::assertTrue($serializer->supports(new ProjectDeleted(
            ProjectId::fromString('018f3f7a-9e4c-7b2d-9c52-00000000abcd'),
            new \DateTimeImmutable('2026-05-05T10:00:00+00:00'),
        )));
    }

    public function testSerializesProjectDeletedEvents(): void
    {
        $serializer = new ProjectDeletedOutboxEventSerializer();
        $deletedAt = new \DateTimeImmutable('2026-05-05T10:00:00+00:00');
        $event = new ProjectDeleted(
            ProjectId::fromString('018f3f7a-9e4c-7b2d-9c52-00000000abcd'),
            $deletedAt,
        );

        $serialized = $serializer->serialize($event, new \DateTimeImmutable('2026-05-05T10:05:00+00:00'));

        self::assertSame('projects.project_deleted', $serialized->eventType);
        self::assertSame('projects.project', $serialized->aggregateType);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-00000000abcd', $serialized->aggregateId);
        self::assertSame([
            'project_id' => '018f3f7a-9e4c-7b2d-9c52-00000000abcd',
            'deleted_at' => '2026-05-05T10:00:00+00:00',
        ], $serialized->payload);
        self::assertSame($deletedAt, $serialized->occurredAt);
        self::assertSame($deletedAt, $serialized->availableAt);
        self::assertEquals(new \DateTimeImmutable('2026-05-05T10:05:00+00:00'), $serialized->createdAt);
    }
}
