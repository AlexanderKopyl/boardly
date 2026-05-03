<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

/**
 * @phpstan-type OutboxMessagePayload array<string, mixed>
 */
final readonly class OutboxMessage
{
    /**
     * @param OutboxMessagePayload $payload
     */
    public function __construct(
        public string $outboxId,
        public string $eventId,
        public string $eventType,
        public ?string $aggregateType,
        public ?string $aggregateId,
        public array $payload,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function fromRecord(OutboxRecord $record): self
    {
        return new self(
            $record->id,
            $record->eventId,
            $record->eventType,
            $record->aggregateType,
            $record->aggregateId,
            $record->payload,
            $record->occurredAt,
        );
    }
}
