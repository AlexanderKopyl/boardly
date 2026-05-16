<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Model;

use App\Boardly\Projects\Domain\Event\ProjectArchived;
use App\Boardly\Projects\Domain\Event\ProjectCreated;
use App\Boardly\Projects\Domain\Event\ProjectDeleted;
use App\Boardly\Projects\Domain\Exception\ProjectAlreadyArchived;
use App\Boardly\Projects\Domain\Exception\InvalidProjectLifecycleTransition;
use App\Boardly\Projects\Domain\Result\ProjectArchivedResult;
use App\Boardly\Projects\Domain\Result\ProjectCreatedResult;
use App\Boardly\Projects\Domain\Result\ProjectDeletedResult;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final class Project
{
    private function __construct(
        private readonly ProjectId $id,
        private readonly AccountId $ownerAccountId,
        private ProjectName $name,
        private ProjectIconKey $iconKey,
        private ProjectStatus $status,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
        private ?\DateTimeImmutable $archivedAt,
        private ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function create(
        ProjectId $id,
        AccountId $ownerAccountId,
        ProjectName $name,
        ProjectIconKey $iconKey,
        \DateTimeImmutable $createdAt,
    ): ProjectCreatedResult {
        $project = new self(
            $id,
            $ownerAccountId,
            $name,
            $iconKey,
            ProjectStatus::active(),
            $createdAt,
            $createdAt,
            null,
            null,
        );

        return new ProjectCreatedResult(
            $project,
            new ProjectCreated($id, $ownerAccountId, $name, $iconKey, $createdAt),
        );
    }

    public static function reconstitute(
        ProjectId $id,
        AccountId $ownerAccountId,
        ProjectName $name,
        ProjectIconKey $iconKey,
        ProjectStatus $status,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $archivedAt,
        ?\DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            $id,
            $ownerAccountId,
            $name,
            $iconKey,
            $status,
            $createdAt,
            $updatedAt,
            $archivedAt,
            $deletedAt,
        );
    }

    public function archive(\DateTimeImmutable $archivedAt): ProjectArchivedResult
    {
        if ($this->status->isDeleted()) {
            throw InvalidProjectLifecycleTransition::deletedProjectIsTerminal();
        }

        if ($this->status->isArchived()) {
            throw ProjectAlreadyArchived::create();
        }

        $this->status = ProjectStatus::archived();
        $this->updatedAt = $archivedAt;
        $this->archivedAt = $archivedAt;

        return new ProjectArchivedResult(
            new ProjectArchived($this->id, $archivedAt),
        );
    }

    public function delete(\DateTimeImmutable $deletedAt): ProjectDeletedResult
    {
        if ($this->status->isDeleted()) {
            throw InvalidProjectLifecycleTransition::deletedProjectIsTerminal();
        }

        $this->status = ProjectStatus::deleted();
        $this->updatedAt = $deletedAt;
        $this->deletedAt = $deletedAt;

        return new ProjectDeletedResult(
            new ProjectDeleted($this->id, $deletedAt),
        );
    }

    public function id(): ProjectId
    {
        return $this->id;
    }

    public function ownerAccountId(): AccountId
    {
        return $this->ownerAccountId;
    }

    public function name(): ProjectName
    {
        return $this->name;
    }

    public function iconKey(): ProjectIconKey
    {
        return $this->iconKey;
    }

    public function status(): ProjectStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function archivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
