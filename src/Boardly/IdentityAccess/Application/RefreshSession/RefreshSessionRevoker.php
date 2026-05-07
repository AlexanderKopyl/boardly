<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshSession;

use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Shared\Application\Port\ClockInterface;

final readonly class RefreshSessionRevoker
{
    public function __construct(
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshSessionRepositoryInterface $refreshSessions,
        private ClockInterface $clock,
    ) {
    }

    public function revoke(string $rawRefreshToken): RefreshSessionRevocationResult
    {
        $tokenHash = $this->refreshTokenHasher->hash($rawRefreshToken);
        $session = $this->refreshSessions->findByTokenHashForUpdate($tokenHash);

        if (null === $session) {
            return RefreshSessionRevocationResult::idempotentSuccess();
        }

        $session->revoke($this->clock->now());
        $this->refreshSessions->save($session);

        return RefreshSessionRevocationResult::revoked();
    }
}
