<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Response;

use App\Boardly\Projects\Application\CreateProject\CreateProjectResult;

final readonly class CreateProjectResponseDto
{
    public function __construct(
        public string $projectId,
        public string $status,
    ) {
    }

    public static function fromResult(CreateProjectResult $result): self
    {
        return new self(
            $result->projectId(),
            $result->status(),
        );
    }

    /**
     * @return array{projectId: string, status: string}
     */
    public function toArray(): array
    {
        return [
            'projectId' => $this->projectId,
            'status' => $this->status,
        ];
    }
}
