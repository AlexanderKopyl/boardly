<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\RefreshAuthentication;

use RuntimeException;

final class InvalidRefreshToken extends RuntimeException
{
    public static function create(): self
    {
        return new self('Invalid refresh token.');
    }
}
