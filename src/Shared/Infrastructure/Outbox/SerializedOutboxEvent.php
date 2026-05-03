<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

/**
 * @phpstan-type OutboxPayload array<string, bool|float|int|string|null>
 */
final readonly class SerializedOutboxEvent
{
    /**
     * @param OutboxPayload $payload
     */
    public function __construct(
        public string $eventId,
        public string $eventType,
        public string $aggregateType,
        public string $aggregateId,
        public array $payload,
        public \DateTimeImmutable $occurredAt,
        public \DateTimeImmutable $availableAt,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
