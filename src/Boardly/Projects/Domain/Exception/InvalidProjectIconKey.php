<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Exception;

use InvalidArgumentException;

final class InvalidProjectIconKey extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Project icon key must not be empty.');
    }

    public static function invalidFormat(string $value): self
    {
        return new self(sprintf('Project icon key must be lowercase alphanumeric, starting with a letter, and up to 64 characters. "%s" given.', $value));
    }
}
