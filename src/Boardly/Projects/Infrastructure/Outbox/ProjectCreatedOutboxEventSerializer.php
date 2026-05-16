<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Outbox;

use App\Boardly\Projects\Domain\Event\ProjectCreated;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerInterface;
use App\Shared\Infrastructure\Outbox\SerializedOutboxEvent;
use Symfony\Component\Uid\Uuid;

final class ProjectCreatedOutboxEventSerializer implements OutboxEventSerializerInterface
{
    public function supports(DomainEvent $event): bool
    {
        return $event instanceof ProjectCreated;
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        /** @var ProjectCreated $event */
        $projectId = $event->projectId()->value();
        $ownerAccountId = $event->ownerAccountId()->value();
        $createdAt = $event->createdAt();

        return new SerializedOutboxEvent(
            eventId: Uuid::v7()->toRfc4122(),
            eventType: 'projects.project_created',
            aggregateType: 'projects.project',
            aggregateId: $projectId,
            payload: [
                'project_id' => $projectId,
                'owner_account_id' => $ownerAccountId,
                'name' => $event->name()->value(),
                'icon_key' => $event->iconKey()->value(),
                'created_at' => $createdAt->format(\DateTimeInterface::ATOM),
            ],
            occurredAt: $createdAt,
            availableAt: $createdAt,
            createdAt: $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }
}
