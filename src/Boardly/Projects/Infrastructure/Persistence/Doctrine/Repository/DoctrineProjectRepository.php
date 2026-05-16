<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Entity\ProjectEntity;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Mapper\ProjectMapper;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectMapper $mapper,
    ) {
    }

    public function save(Project $project): void
    {
        $entity = $this->entityManager->find(ProjectEntity::class, $project->id()->value());

        if (!$entity instanceof ProjectEntity) {
            $this->entityManager->persist($this->mapper->toEntity($project));

            return;
        }

        $this->mapper->updateEntity($project, $entity);
    }

    public function get(ProjectId $id): Project
    {
        return $this->find($id) ?? throw ProjectNotFound::withId($id);
    }

    public function find(ProjectId $id): ?Project
    {
        $entity = $this->entityManager->find(ProjectEntity::class, $id->value());

        if (!$entity instanceof ProjectEntity) {
            return null;
        }

        return $this->mapper->toDomain($entity);
    }

    public function findByOwner(AccountId $ownerId): array
    {
        $entities = $this->entityManager
            ->getRepository(ProjectEntity::class)
            ->findBy(['ownerAccountId' => $ownerId->value()]);

        return array_map(
            fn (ProjectEntity $entity) => $this->mapper->toDomain($entity),
            $entities,
        );
    }
}
