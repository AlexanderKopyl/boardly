<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Result;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;

final readonly class AccountRegistrationResult
{
    public function __construct(
        private AccountRegistered $event,
    ) {
    }

    public function event(): AccountRegistered
    {
        return $this->event;
    }
}
