<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\GetProject;

final readonly class GetProjectQuery
{
    public function __construct(
        private string $projectId,
        private string $currentAccountId,
    ) {
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function currentAccountId(): string
    {
        return $this->currentAccountId;
    }
}
