<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\CreateProject;

final readonly class CreateProjectCommand
{
    public function __construct(
        private string $ownerAccountId,
        private string $name,
        private ?string $iconKey = null,
    ) {
    }

    public function ownerAccountId(): string
    {
        return $this->ownerAccountId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function iconKey(): ?string
    {
        return $this->iconKey;
    }
}
