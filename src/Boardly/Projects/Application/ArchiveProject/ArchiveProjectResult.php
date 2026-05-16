<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ArchiveProject;

final readonly class ArchiveProjectResult
{
    public function __construct(
        private string $projectId,
        private string $status,
        private string $archivedAt,
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

    public function archivedAt(): string
    {
        return $this->archivedAt;
    }
}
