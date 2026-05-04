<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\AuthenticateAccount;

use RuntimeException;

final class InvalidCredentials extends RuntimeException
{
    public static function create(): self
    {
        return new self('Invalid credentials.');
    }
}
