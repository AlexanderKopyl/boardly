<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper;

use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\RefreshSessionEntity;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;

final class RefreshSessionMapper
{
    public function toEntity(RefreshSession $session, AccountEntity $accountEntity): RefreshSessionEntity
    {
        return new RefreshSessionEntity(
            $session->id()->value(),
            $accountEntity,
            $session->tokenHash()->value(),
            $session->familyId()->value(),
            $session->expiresAt(),
            $session->revokedAt(),
            $session->replacedByTokenId()?->value(),
            $session->createdAt(),
            $session->lastUsedAt(),
            $session->userAgentHash(),
            $session->ipHash(),
        );
    }

    public function updateEntity(RefreshSession $session, RefreshSessionEntity $entity): void
    {
        if ($session->id()->value() !== $entity->getId()) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot update refresh session entity "%s" from refresh session "%s".',
                $entity->getId(),
                $session->id()->value(),
            ));
        }

        $entity->updateMutableFields(
            $session->tokenHash()->value(),
            $session->expiresAt(),
            $session->revokedAt(),
            $session->replacedByTokenId()?->value(),
            $session->lastUsedAt(),
            $session->userAgentHash(),
            $session->ipHash(),
        );
    }

    public function toDomain(RefreshSessionEntity $entity): RefreshSession
    {
        return RefreshSession::reconstitute(
            RefreshSessionId::fromString($entity->getId()),
            AccountId::fromString($entity->getAccountId()),
            RefreshTokenHash::fromString($entity->getTokenHash()),
            RefreshSessionFamilyId::fromString($entity->getFamilyId()),
            $entity->getExpiresAt(),
            $entity->getRevokedAt(),
            null !== $entity->getReplacedByTokenId()
                ? RefreshSessionId::fromString($entity->getReplacedByTokenId())
                : null,
            $entity->getCreatedAt(),
            $entity->getLastUsedAt(),
            $entity->getUserAgentHash(),
            $entity->getIpHash(),
        );
    }
}
