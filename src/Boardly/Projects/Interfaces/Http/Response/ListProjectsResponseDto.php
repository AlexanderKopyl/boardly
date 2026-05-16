<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Response;

use App\Boardly\Projects\Application\ListProjects\ListProjectsResult;
use App\Boardly\Projects\Application\ListProjects\ProjectListItem;

final readonly class ListProjectsResponseDto
{
    /**
     * @param ProjectListItemResponseDto[] $projects
     */
    public function __construct(
        public array $projects,
    ) {
    }

    public static function fromResult(ListProjectsResult $result): self
    {
        return new self(array_map(
            static fn (ProjectListItem $item): ProjectListItemResponseDto => ProjectListItemResponseDto::fromItem($item),
            $result->projects(),
        ));
    }

    /**
     * @return array{projects: array<int, array{id: string, name: string, iconKey: string, status: string, createdAt: string}>}
     */
    public function toArray(): array
    {
        return [
            'projects' => array_map(
                static fn (ProjectListItemResponseDto $item): array => $item->toArray(),
                $this->projects,
            ),
        ];
    }
}
