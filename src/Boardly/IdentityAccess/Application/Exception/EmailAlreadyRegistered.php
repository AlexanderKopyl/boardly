<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Exception;

use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use RuntimeException;

final class EmailAlreadyRegistered extends RuntimeException
{
    public static function withEmail(Email $email): self
    {
        return new self('Email is already registered.');
    }
}
