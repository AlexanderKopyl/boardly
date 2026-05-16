<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Message;

final readonly class ProjectCreatedMessage
{
    public function __construct(
        public string $outboxId,
        public string $eventId,
        public string $projectId,
        public string $ownerAccountId,
        public string $name,
        public string $iconKey,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
