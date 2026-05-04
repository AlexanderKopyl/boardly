<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Repository;

use App\Boardly\IdentityAccess\Domain\Model\RefreshSession;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionFamilyId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshSessionId;
use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;

interface RefreshSessionRepositoryInterface
{
    public function save(RefreshSession $session): void;

    public function find(RefreshSessionId $id): ?RefreshSession;

    public function findByTokenHash(RefreshTokenHash $tokenHash): ?RefreshSession;

    public function revokeFamily(RefreshSessionFamilyId $familyId, \DateTimeImmutable $revokedAt): void;
}
