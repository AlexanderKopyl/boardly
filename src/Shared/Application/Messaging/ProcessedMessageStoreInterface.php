<?php

declare(strict_types=1);

namespace App\Shared\Application\Messaging;

interface ProcessedMessageStoreInterface
{
    /**
     * Starts processing for a message/handler pair.
     *
     * Returns false when the same message has already been started or processed
     * by the same handler. The default idempotency key is event id + handler name.
     */
    public function tryStart(string $eventId, string $handlerName): bool;

    public function markProcessed(string $eventId, string $handlerName): void;
}
