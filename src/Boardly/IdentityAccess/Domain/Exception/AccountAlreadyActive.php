<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use DomainException;

final class AccountAlreadyActive extends DomainException
{
    public static function create(): self
    {
        return new self('Account is already active.');
    }
}
