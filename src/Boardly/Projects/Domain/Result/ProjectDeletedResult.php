<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Result;

use App\Boardly\Projects\Domain\Event\ProjectDeleted;

final readonly class ProjectDeletedResult
{
    public function __construct(
        private ProjectDeleted $event,
    ) {
    }

    public function event(): ProjectDeleted
    {
        return $this->event;
    }
}
