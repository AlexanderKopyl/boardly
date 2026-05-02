<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use InvalidArgumentException;

final class InvalidPasswordHash extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Password hash must not be empty.');
    }

    public static function unsupportedFormat(): self
    {
        return new self('Password hash must be an already-created supported password hash.');
    }
}
