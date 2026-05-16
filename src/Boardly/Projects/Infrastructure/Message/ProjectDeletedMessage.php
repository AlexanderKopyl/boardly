<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Message;

final readonly class ProjectDeletedMessage
{
    public function __construct(
        public string $outboxId,
        public string $eventId,
        public string $projectId,
        public \DateTimeImmutable $deletedAt,
    ) {
    }
}
