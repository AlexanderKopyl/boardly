<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Logout;

use App\Boardly\IdentityAccess\Application\Port\RefreshTokenHasherInterface;
use App\Boardly\IdentityAccess\Application\Repository\RefreshSessionRepositoryInterface;
use App\Shared\Application\Port\ClockInterface;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class LogoutHandler
{
    public function __construct(
        private RefreshTokenHasherInterface $refreshTokenHasher,
        private RefreshSessionRepositoryInterface $refreshSessions,
        private TransactionalInterface $transactional,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(LogoutCommand $command): LogoutResult
    {
        $rawRefreshToken = $command->rawRefreshToken();

        if (null === $rawRefreshToken || '' === trim($rawRefreshToken)) {
            return LogoutResult::success();
        }

        $tokenHash = $this->refreshTokenHasher->hash($rawRefreshToken);
        $session = $this->refreshSessions->findByTokenHash($tokenHash);

        if (null === $session) {
            return LogoutResult::success();
        }

        $now = $this->clock->now();

        $this->transactional->transactional(function () use ($session, $now): void {
            $session->revoke($now);
            $this->refreshSessions->save($session);
        });

        return LogoutResult::success();
    }
}
