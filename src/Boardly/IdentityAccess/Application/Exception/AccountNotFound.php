<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Application\Exception;

use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use RuntimeException;

final class AccountNotFound extends RuntimeException
{
    public static function withId(AccountId $id): self
    {
        return new self('Account was not found.');
    }
}
