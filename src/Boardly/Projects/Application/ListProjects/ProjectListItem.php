<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\ListProjects;

final readonly class ProjectListItem
{
    public function __construct(
        public string $id,
        public string $name,
        public string $iconKey,
        public string $status,
        public string $createdAt,
    ) {
    }
}
