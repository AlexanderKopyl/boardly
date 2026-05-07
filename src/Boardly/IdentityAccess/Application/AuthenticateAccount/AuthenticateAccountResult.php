<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

use DateTimeImmutable;

final readonly class AuthenticateAccountResult
{
    public function __construct(
        private string $accessToken,
        private DateTimeImmutable $accessTokenExpiresAt,
        private int $accessTokenExpiresInSeconds,
        private string $rawRefreshToken,
        private DateTimeImmutable $refreshTokenExpiresAt,
        private AuthenticatedAccount $account,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function accessTokenExpiresAt(): DateTimeImmutable
    {
        return $this->accessTokenExpiresAt;
    }

    public function accessTokenExpiresInSeconds(): int
    {
        return $this->accessTokenExpiresInSeconds;
    }

    public function rawRefreshToken(): string
    {
        return $this->rawRefreshToken;
    }

    public function refreshTokenExpiresAt(): DateTimeImmutable
    {
        return $this->refreshTokenExpiresAt;
    }

    public function account(): AuthenticatedAccount
    {
        return $this->account;
    }
}
