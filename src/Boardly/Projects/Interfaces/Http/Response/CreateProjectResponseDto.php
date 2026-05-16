<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Response;

use App\Boardly\Projects\Application\CreateProject\CreateProjectResult;

final readonly class CreateProjectResponseDto
{
    public function __construct(
        public string $id,
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
     * @return array{id: string, status: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
