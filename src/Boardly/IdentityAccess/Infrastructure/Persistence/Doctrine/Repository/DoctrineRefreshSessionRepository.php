<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\RefreshSessionEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\RefreshSessionMapper;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineRefreshSessionRepository implements RefreshSessionRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RefreshSessionMapper $mapper,
    ) {
    }

    public function save(RefreshSession $session): void
    {
        $entity = $this->entityManager->find(RefreshSessionEntity::class, $session->id()->value());

        if (!$entity instanceof RefreshSessionEntity) {
            $accountEntity = $this->entityManager->getReference(AccountEntity::class, $session->accountId()->value());

            if (!$accountEntity instanceof AccountEntity) {
                throw new \RuntimeException(sprintf(
                    'Cannot create refresh session for missing account "%s".',
                    $session->accountId()->value(),
                ));
            }

            $this->entityManager->persist($this->mapper->toEntity($session, $accountEntity));

            return;
        }

        $this->mapper->updateEntity($session, $entity);
    }

    public function find(RefreshSessionId $id): ?RefreshSession
    {
        $entity = $this->entityManager->find(RefreshSessionEntity::class, $id->value());

        if (!$entity instanceof RefreshSessionEntity) {
            return null;
        }

        return $this->mapper->toDomain($entity);
    }

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshSession
    {
        $entity = $this->entityManager
            ->getRepository(RefreshSessionEntity::class)
            ->findOneBy(['tokenHash' => $tokenHash->value()]);

        if (!$entity instanceof RefreshSessionEntity) {
            return null;
        }

        return $this->mapper->toDomain($entity);
    }

    public function revokeFamily(RefreshSessionFamilyId $familyId, \DateTimeImmutable $revokedAt): void
    {
        $entities = $this->entityManager
            ->getRepository(RefreshSessionEntity::class)
            ->findBy(['familyId' => $familyId->value()]);

        foreach ($entities as $entity) {
            $entity->revoke($revokedAt);
        }
    }
}
