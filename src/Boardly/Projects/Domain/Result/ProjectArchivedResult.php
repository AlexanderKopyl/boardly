<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Result;

use App\Boardly\Projects\Domain\Event\ProjectArchived;

final readonly class ProjectArchivedResult
{
    public function __construct(
        private ?ProjectArchived $event,
    ) {
    }

    public function event(): ?ProjectArchived
    {
        return $this->event;
    }
}
