<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Domain\Event\ProjectDeleted;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface;
use App\Shared\Infrastructure\Outbox\SerializedOutboxEvent;
use Symfony\Component\Uid\Uuid;

final class ProjectDeletedOutboxEventSerializer implements OutboxEventSerializerInterface
{
    public function supports(DomainEvent $event): bool
    {
        return $event instanceof ProjectDeleted;
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        /** @var ProjectDeleted $event */
        $projectId = $event->projectId()->value();
        $deletedAt = $event->deletedAt();

        return new SerializedOutboxEvent(
            eventId: Uuid::v7()->toRfc4122(),
            eventType: 'projects.project_deleted',
            aggregateType: 'projects.project',
            aggregateId: $projectId,
            payload: [
                'project_id' => $projectId,
                'deleted_at' => $deletedAt->format(\DateTimeInterface::ATOM),
            ],
            occurredAt: $deletedAt,
            availableAt: $deletedAt,
            createdAt: $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
