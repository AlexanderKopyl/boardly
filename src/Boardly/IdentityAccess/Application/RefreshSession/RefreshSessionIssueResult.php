<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshSession;

use DateTimeImmutable;

final readonly class RefreshSessionIssueResult
{
    public function __construct(
        private string $rawRefreshToken,
        private DateTimeImmutable $refreshTokenExpiresAt,
    ) {
    }

    public function rawRefreshToken(): string
    {
        return $this->rawRefreshToken;
    }

    public function refreshTokenExpiresAt(): DateTimeImmutable
    {
        return $this->refreshTokenExpiresAt;
    }
}
