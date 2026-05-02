<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use InvalidArgumentException;

final class InvalidAccountStatus extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Account status must not be empty.');
    }

    public static function unknown(string $value): self
    {
        return new self(sprintf('Unknown account status "%s".', $value));
    }
}
