<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Result;

use App\Boardly\Projects\Domain\Event\ProjectCreated;
use App\Boardly\Projects\Domain\Model\Project;

final readonly class ProjectCreatedResult
{
    public function __construct(
        private Project $project,
        private ProjectCreated $event,
    ) {
    }

    public function project(): Project
    {
        return $this->project;
    }

    public function event(): ProjectCreated
    {
        return $this->event;
    }
}
