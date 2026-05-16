<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Event;

use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final readonly class ProjectArchived implements DomainEvent
{
    public function __construct(
        private ProjectId $projectId,
        private \DateTimeImmutable $archivedAt,
    ) {
    }

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function archivedAt(): \DateTimeImmutable
    {
        return $this->archivedAt;
    }
}
