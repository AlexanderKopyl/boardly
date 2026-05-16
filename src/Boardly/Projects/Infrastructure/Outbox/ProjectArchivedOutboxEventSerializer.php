<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Domain\Event\ProjectArchived;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface;
use App\Shared\Infrastructure\Outbox\SerializedOutboxEvent;
use Symfony\Component\Uid\Uuid;

final class ProjectArchivedOutboxEventSerializer implements OutboxEventSerializerInterface
{
    public function supports(DomainEvent $event): bool
    {
        return $event instanceof ProjectArchived;
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        /** @var ProjectArchived $event */
        $projectId = $event->projectId()->value();
        $archivedAt = $event->archivedAt();

        return new SerializedOutboxEvent(
            eventId: Uuid::v7()->toRfc4122(),
            eventType: 'projects.project_archived',
            aggregateType: 'projects.project',
            aggregateId: $projectId,
            payload: [
                'project_id' => $projectId,
                'archived_at' => $archivedAt->format(\DateTimeInterface::ATOM),
            ],
            occurredAt: $archivedAt,
            availableAt: $archivedAt,
            createdAt: $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
