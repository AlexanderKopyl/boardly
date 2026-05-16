<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\CreateProject;

final readonly class CreateProjectResult
{
    public function __construct(
        private string $projectId,
        private string $status,
    ) {
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function status(): string
    {
        return $this->status;
    }
}
