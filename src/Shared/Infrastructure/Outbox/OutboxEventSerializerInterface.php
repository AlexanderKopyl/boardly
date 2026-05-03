<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;

interface OutboxEventSerializerInterface
{
    public function supports(DomainEvent $event): bool;

    public function serialize(DomainEvent $event, ?\DateTimeImmutable $now = null): SerializedOutboxEvent;
}
