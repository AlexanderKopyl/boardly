<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Response;

use App\Boardly\Projects\Application\ArchiveProject\ArchiveProjectResult;

final readonly class ArchiveProjectResponseDto
{
    public function __construct(
        public string $projectId,
        public string $status,
        public string $archivedAt,
    ) {
    }

    public static function fromResult(ArchiveProjectResult $result): self
    {
        return new self(
            $result->projectId(),
            $result->status(),
            $result->archivedAt(),
        );
    }

    /**
     * @return array{projectId: string, status: string, archivedAt: string}
     */
    public function toArray(): array
    {
        return [
            'projectId' => $this->projectId,
            'status' => $this->status,
            'archivedAt' => $this->archivedAt,
        ];
    }
}
