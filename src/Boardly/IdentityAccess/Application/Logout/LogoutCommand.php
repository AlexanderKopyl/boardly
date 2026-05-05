<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Logout;

final readonly class LogoutCommand
{
    public function __construct(
        private ?string $rawRefreshToken,
    ) {
    }

    public function rawRefreshToken(): ?string
    {
        return $this->rawRefreshToken;
    }
}
