<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;

readonly class OutboxEventSerializerRegistry
{
    /**
     * @param iterable<OutboxEventSerializerInterface> $serializers
     */
    public function __construct(
        private iterable $serializers,
    ) {
    }

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($event)) {
                return $serializer->serialize($event, $now);
            }
        }

        throw new \InvalidArgumentException(sprintf('Unsupported domain event "%s" cannot be serialized for the outbox.', $event::class));
    }
}
