<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\Projects\Application\Exception\ProjectNotFound;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectStatus;
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

    public function getAccessibleById(ProjectId $id, AccountId $currentAccountId): Project
    {
        $entity = $this->entityManager
            ->createQueryBuilder()
            ->select('project')
            ->from(ProjectEntity::class, 'project')
            ->andWhere('project.id = :id')
            ->andWhere('project.ownerAccountId = :ownerAccountId')
            ->andWhere('project.status <> :deletedStatus')
            ->setParameter('id', $id->value())
            ->setParameter('ownerAccountId', $currentAccountId->value())
            ->setParameter('deletedStatus', ProjectStatus::deleted()->value())
            ->getQuery()
            ->getOneOrNullResult();

        if (!$entity instanceof ProjectEntity) {
            throw ProjectNotFound::withId($id);
        }

        return $this->mapper->toDomain($entity);
    }

    public function find(ProjectId $id): ?Project
    {
        $entity = $this->entityManager->find(ProjectEntity::class, $id->value());

        if (!$entity instanceof ProjectEntity) {
            return null;
        }

        return $this->mapper->toDomain($entity);
    }

    public function findAccessibleActiveByOwner(AccountId $ownerId): array
    {
        $entities = $this->entityManager
            ->createQueryBuilder()
            ->select('project')
            ->from(ProjectEntity::class, 'project')
            ->andWhere('project.ownerAccountId = :ownerAccountId')
            ->andWhere('project.status = :activeStatus')
            ->orderBy('project.createdAt', 'ASC')
            ->setParameter('ownerAccountId', $ownerId->value())
            ->setParameter('activeStatus', ProjectStatus::active()->value())
            ->getQuery()
            ->getResult();

        return array_map(
            fn (ProjectEntity $entity) => $this->mapper->toDomain($entity),
            $entities,
        );
    }
}
