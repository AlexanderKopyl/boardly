<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Logout;

use App\Boardly\IdentityAccess\Application\RefreshSession\RefreshSessionRevoker;
use App\Shared\Application\Transaction\TransactionalInterface;

final readonly class LogoutHandler
{
    public function __construct(
        private RefreshSessionRevoker $refreshSessionRevoker,
        private TransactionalInterface $transactional,
    ) {
    }

    public function __invoke(LogoutCommand $command): LogoutResult
    {
        $rawRefreshToken = $command->rawRefreshToken();

        if (null === $rawRefreshToken || '' === trim($rawRefreshToken)) {
            return LogoutResult::success();
        }

        return $this->transactional->transactional(function () use ($rawRefreshToken): LogoutResult {
            $this->refreshSessionRevoker->revoke($rawRefreshToken);

            return LogoutResult::success();
        });
    }
}
