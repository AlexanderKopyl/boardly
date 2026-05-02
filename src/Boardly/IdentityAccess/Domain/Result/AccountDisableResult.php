<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Domain\Result;

use App\Boardly\IdentityAccess\Domain\Event\AccountDisabled;

final readonly class AccountDisableResult
{
    public function __construct(
        private AccountDisabled $event,
    ) {
    }

    public function event(): AccountDisabled
    {
        return $this->event;
    }
}
