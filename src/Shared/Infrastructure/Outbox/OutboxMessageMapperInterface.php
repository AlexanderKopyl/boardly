<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox;

interface OutboxMessageMapperInterface
{
    public function supports(OutboxRecord $record): bool;

    public function map(OutboxRecord $record): object;
}
