<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Exception;

use InvalidArgumentException;

final class InvalidProjectStatus extends InvalidArgumentException
{
    public static function unknown(string $value): self
    {
        return new self(sprintf('Unknown ProjectStatus: "%s".', $value));
    }

    public static function empty(): self
    {
        return new self('ProjectStatus must not be empty.');
    }
}
