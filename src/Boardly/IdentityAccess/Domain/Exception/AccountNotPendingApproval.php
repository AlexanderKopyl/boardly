<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Exception;

use DomainException;

final class AccountNotPendingApproval extends DomainException
{
    public static function create(): self
    {
        return new self('Account must be pending approval for this transition.');
    }
}
