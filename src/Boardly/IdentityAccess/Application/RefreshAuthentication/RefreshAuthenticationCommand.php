<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshAuthentication;

final readonly class RefreshAuthenticationCommand
{
    public function __construct(
        private string $rawRefreshToken,
        private ?string $userAgent,
        private ?string $ipAddress,
    ) {
    }

    public function rawRefreshToken(): string
    {
        return $this->rawRefreshToken;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }
}
