<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Domain\Exception;

use InvalidArgumentException;

final class InvalidProjectName extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Project name must not be empty.');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(sprintf('Project name must not exceed %d characters.', $maxLength));
    }
}
