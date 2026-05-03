<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

/**
 * @phpstan-type OutboxRecordPayload array<string, mixed>
 */
final readonly class OutboxRecord
{
    /**
     * @param OutboxRecordPayload $payload
     */
    public function __construct(
        public string $id,
        public string $eventId,
        public string $eventType,
        public ?string $aggregateType,
        public ?string $aggregateId,
        public array $payload,
        public \DateTimeImmutable $occurredAt,
        public \DateTimeImmutable $availableAt,
        public ?\DateTimeImmutable $publishedAt,
        public int $attempts,
        public ?string $lastError,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
