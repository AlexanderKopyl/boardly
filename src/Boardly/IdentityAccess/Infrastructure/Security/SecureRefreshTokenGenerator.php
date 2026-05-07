<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Port\RefreshTokenGeneratorInterface;

final readonly class SecureRefreshTokenGenerator implements RefreshTokenGeneratorInterface
{
    private const int RANDOM_BYTES = 32;

    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::RANDOM_BYTES)), '+/', '-_'), '=');
    }
}
