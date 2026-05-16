<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Response;

use App\Boardly\Projects\Application\ListProjects\ProjectListItem;

final readonly class ProjectListItemResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $iconKey,
        public string $status,
        public string $createdAt,
    ) {
    }

    public static function fromItem(ProjectListItem $item): self
    {
        return new self(
            $item->id,
            $item->name,
            $item->iconKey,
            $item->status,
            $item->createdAt,
        );
    }

    /**
     * @return array{id: string, name: string, iconKey: string, status: string, createdAt: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'iconKey' => $this->iconKey,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
