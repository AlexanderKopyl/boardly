<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Application\GetProject;

final readonly class GetProjectResult
{
    public function __construct(
        private string $id,
        private string $name,
        private string $iconKey,
        private string $status,
        private string $createdAt,
        private string $updatedAt,
        private ?string $archivedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function iconKey(): string
    {
        return $this->iconKey;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): string
    {
        return $this->createdAt;
    }

    public function updatedAt(): string
    {
        return $this->updatedAt;
    }

    public function archivedAt(): ?string
    {
        return $this->archivedAt;
    }
}
