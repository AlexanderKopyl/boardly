<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Mapper;

use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Entity\ProjectEntity;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Exception\ProjectMappingFailed;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;

final class ProjectMapper
{
    public function toEntity(Project $project): ProjectEntity
    {
        return new ProjectEntity(
            $project->id()->value(),
            $project->ownerAccountId()->value(),
            $project->name()->value(),
            $project->iconKey()->value(),
            $project->status()->value(),
            $project->createdAt(),
            $project->updatedAt(),
            $project->archivedAt(),
            $project->deletedAt(),
        );
    }

    public function updateEntity(Project $project, ProjectEntity $entity): void
    {
        if ($project->id()->value() !== $entity->getId()) {
            throw ProjectMappingFailed::forMismatchedProjectId($project->id()->value(), $entity->getId());
        }

        $entity->updateMutableFields(
            $project->name()->value(),
            $project->iconKey()->value(),
            $project->status()->value(),
            $project->updatedAt(),
            $project->archivedAt(),
            $project->deletedAt(),
        );
    }

    public function toDomain(ProjectEntity $entity): Project
    {
        return Project::reconstitute(
            ProjectId::fromString($entity->getId()),
            AccountId::fromString($entity->getOwnerAccountId()),
            ProjectName::fromString($entity->getName()),
            ProjectIconKey::fromString($entity->getIconKey()),
            ProjectStatus::fromString($entity->getStatus()),
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
            $entity->getArchivedAt(),
            $entity->getDeletedAt(),
        );
    }
}
