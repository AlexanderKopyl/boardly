<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ListProjects;

final readonly class ListProjectsQuery
{
    public function __construct(
        private string $ownerAccountId,
    ) {
    }

    public function ownerAccountId(): string
    {
        return $this->ownerAccountId;
    }
}
