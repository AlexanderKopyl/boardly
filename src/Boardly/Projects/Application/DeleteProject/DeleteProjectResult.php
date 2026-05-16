<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\DeleteProject;

final readonly class DeleteProjectResult
{
    public function __construct(
        private string $projectId,
        private string $status,
        private string $deletedAt,
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

    public function deletedAt(): string
    {
        return $this->deletedAt;
    }
}
