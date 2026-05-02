<?php

declare(strict_types=1);

namespace App\Boardly\SharedKernel\Domain\Exception;

use InvalidArgumentException;

final class InvalidAccountId extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('AccountId must not be empty.');
    }

    public static function invalidUuid(string $value): self
    {
        return new self(sprintf('AccountId must be a valid UUID. "%s" given.', $value));
    }
}
