<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Response;

use App\Boardly\Projects\Application\GetProject\GetProjectResult;

final readonly class GetProjectResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $iconKey,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?string $archivedAt,
    ) {
    }

    public static function fromResult(GetProjectResult $result): self
    {
        return new self(
            $result->id(),
            $result->name(),
            $result->iconKey(),
            $result->status(),
            $result->createdAt(),
            $result->updatedAt(),
            $result->archivedAt(),
        );
    }

    /**
     * @return array{id: string, name: string, iconKey: string, status: string, createdAt: string, updatedAt: string, archivedAt: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'iconKey' => $this->iconKey,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'archivedAt' => $this->archivedAt,
        ];
    }
}
