<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use DomainException;

final class InvalidAccountStatusTransition extends DomainException
{
    public static function create(): self
    {
        return new self('Account status transition is not allowed.');
    }
}
