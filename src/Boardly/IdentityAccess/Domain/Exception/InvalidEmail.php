<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use InvalidArgumentException;

final class InvalidEmail extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Email must not be empty.');
    }

    public static function invalidFormat(): self
    {
        return new self('Email must be a valid email address.');
    }
}
