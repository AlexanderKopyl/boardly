<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

readonly class OutboxMessageMapperRegistry
{
    /**
     * @param iterable<OutboxMessageMapperInterface> $mappers
     */
    public function __construct(
        private iterable $mappers,
    ) {
    }

    public function map(OutboxRecord $record): object
    {
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($record)) {
                return $mapper->map($record);
            }
        }

        throw new \InvalidArgumentException(sprintf('Unsupported outbox record event type "%s".', $record->eventType));
    }
}
