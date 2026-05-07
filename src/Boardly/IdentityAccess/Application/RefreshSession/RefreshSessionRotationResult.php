<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshSession;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use DateTimeImmutable;

final readonly class RefreshSessionRotationResult
{
    public function __construct(
        private AccountId $accountId,
        private string $rawRefreshToken,
        private DateTimeImmutable $refreshTokenExpiresAt,
    ) {
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
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
