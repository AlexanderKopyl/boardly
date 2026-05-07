<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Exception;

final class InvalidRefreshToken extends IdentityAccessApplicationException
{
    public static function create(): self
    {
        return new self('Invalid refresh token.');
    }
}
