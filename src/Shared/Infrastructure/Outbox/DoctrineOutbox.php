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

    /**
     * @return list<OutboxRecord>
     */
    public function loadUnpublished(int $limit, ?\DateTimeImmutable $now = null): array
    {
        if ($limit < 1) {
            return [];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(
                'id',
                'event_id',
                'event_type',
                'aggregate_type',
                'aggregate_id',
                'payload',
                'occurred_at',
                'available_at',
                'published_at',
                'attempts',
                'last_error',
                'created_at',
            )
            ->from('outbox_messages')
            ->where('published_at IS NULL')
            ->andWhere('available_at <= :now')
            ->setParameter('now', $now ?? new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->orderBy('available_at', 'ASC')
            ->addOrderBy('created_at', 'ASC')
            ->setMaxResults($limit);

        return array_map(
            fn (array $row): OutboxRecord => $this->mapRowToRecord($row),
            $queryBuilder->executeQuery()->fetchAllAssociative(),
        );
    }

    public function markPublished(string $id, ?\DateTimeImmutable $publishedAt = null): void
    {
        $this->connection->update(
            'outbox_messages',
            [
                'published_at' => $publishedAt ?? new \DateTimeImmutable(),
            ],
            [
                'id' => $id,
            ],
            [
                'published_at' => Types::DATETIME_IMMUTABLE,
                'id' => Types::GUID,
            ],
        );
    }

    public function recordFailure(string $id, string $error, ?\DateTimeImmutable $nextAvailableAt = null): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->update('outbox_messages')
            ->set('attempts', 'attempts + 1')
            ->set('last_error', ':last_error')
            ->where('id = :id')
            ->setParameter('id', $id, Types::GUID)
            ->setParameter('last_error', $error, Types::TEXT);

        if (null !== $nextAvailableAt) {
            $queryBuilder
                ->set('available_at', ':available_at')
                ->setParameter('available_at', $nextAvailableAt, Types::DATETIME_IMMUTABLE);
        }

        $queryBuilder->executeStatement();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToRecord(array $row): OutboxRecord
    {
        return new OutboxRecord(
            (string) $row['id'],
            (string) $row['event_id'],
            (string) $row['event_type'],
            null === $row['aggregate_type'] ? null : (string) $row['aggregate_type'],
            null === $row['aggregate_id'] ? null : (string) $row['aggregate_id'],
            $this->decodePayload($row['payload']),
            $this->dateTimeFromDatabase($row['occurred_at']),
            $this->dateTimeFromDatabase($row['available_at']),
            null === $row['published_at'] ? null : $this->dateTimeFromDatabase($row['published_at']),
            (int) $row['attempts'],
            null === $row['last_error'] ? null : (string) $row['last_error'],
            $this->dateTimeFromDatabase($row['created_at']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        $decoded = json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new \UnexpectedValueException('Outbox payload must decode to an array.');
        }

        return $decoded;
    }

    private function dateTimeFromDatabase(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        return new \DateTimeImmutable((string) $value);
    }
}
