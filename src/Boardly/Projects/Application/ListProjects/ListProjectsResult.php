<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ListProjects;

final readonly class ListProjectsResult
{
    /**
     * @param ProjectListItem[] $projects
     */
    public function __construct(
        private array $projects,
    ) {
    }

    /**
     * @return ProjectListItem[]
     */
    public function projects(): array
    {
        return $this->projects;
    }
}
