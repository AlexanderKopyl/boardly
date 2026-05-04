<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Messaging\ProcessedMessageStoreInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;

final readonly class DoctrineProcessedMessageStore implements ProcessedMessageStoreInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function tryStart(string $eventId, string $handlerName): bool
    {
        try {
            $this->connection->insert('processed_messages', [
                'event_id' => $eventId,
                'handler_name' => $handlerName,
                'status' => 'started',
                'started_at' => new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                'processed_at' => null,
            ], [
                'event_id' => Types::GUID,
                'handler_name' => Types::STRING,
                'status' => Types::STRING,
                'started_at' => Types::DATETIME_IMMUTABLE,
                'processed_at' => Types::DATETIME_IMMUTABLE,
            ]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    public function markProcessed(string $eventId, string $handlerName): void
    {
        $this->connection->update(
            'processed_messages',
            [
                'status' => 'processed',
                'processed_at' => new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            ],
            [
                'event_id' => $eventId,
                'handler_name' => $handlerName,
            ],
            [
                'status' => Types::STRING,
                'processed_at' => Types::DATETIME_IMMUTABLE,
                'event_id' => Types::GUID,
                'handler_name' => Types::STRING,
            ],
        );
    }
}
