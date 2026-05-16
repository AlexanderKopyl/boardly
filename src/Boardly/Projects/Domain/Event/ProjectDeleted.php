<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Event;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final readonly class ProjectDeleted implements DomainEvent
{
    public function __construct(
        private ProjectId $projectId,
        private \DateTimeImmutable $deletedAt,
    ) {
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function deletedAt(): \DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
