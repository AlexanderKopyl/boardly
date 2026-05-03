<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Shared\Application\Outbox\OutboxInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineOutbox implements OutboxInterface
{
    public function __construct(
        private Connection $connection,
        private OutboxEventSerializer $serializer,
    ) {
    }

    /**
     * @param list<DomainEvent> $events
     */
    public function store(array $events): void
    {
        foreach ($events as $event) {
            $serialized = $this->serializer->serialize($event);

            $this->connection->insert('outbox_messages', [
                'id' => Uuid::v7()->toRfc4122(),
                'event_id' => $serialized->eventId,
                'event_type' => $serialized->eventType,
                'aggregate_type' => $serialized->aggregateType,
                'aggregate_id' => $serialized->aggregateId,
                'payload' => $serialized->payload,
                'occurred_at' => $serialized->occurredAt,
                'available_at' => $serialized->availableAt,
                'published_at' => null,
                'attempts' => 0,
                'last_error' => null,
                'created_at' => $serialized->createdAt,
            ], [
                'id' => Types::GUID,
                'event_id' => Types::GUID,
                'event_type' => Types::STRING,
                'aggregate_type' => Types::STRING,
                'aggregate_id' => Types::STRING,
                'payload' => Types::JSON,
                'occurred_at' => Types::DATETIME_IMMUTABLE,
                'available_at' => Types::DATETIME_IMMUTABLE,
                'published_at' => Types::DATETIME_IMMUTABLE,
                'attempts' => Types::INTEGER,
                'last_error' => Types::TEXT,
                'created_at' => Types::DATETIME_IMMUTABLE,
            ]);
        }
    }
}
