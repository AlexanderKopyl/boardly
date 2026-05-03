<?php

declare(strict_types=1);

namespace App\Shared\Application\Outbox;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;

interface OutboxInterface
{
    /**
     * Stores domain events durably through an application port.
     *
     * @param list<DomainEvent> $events
     */
    public function store(array $events): void;
}
