<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use InvalidArgumentException;

final class InvalidAccountName extends InvalidArgumentException
{
    public static function empty(): self
    {
        return new self('Account name must not be empty.');
    }

    public static function tooLong(int $maxLength): self
    {
        return new self(sprintf('Account name must not be longer than %d characters.', $maxLength));
    }
}
